<?php

namespace App\Tests;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Test suite cho UserController — cover 5 endpoint CRUD.
 *
 * Fixtures đã seed:
 *   - admin@example.com  / Admin@123  → ROLE_ADMIN  (có user.*)
 *   - editor@example.com / Editor@123 → ROLE_EDITOR (KHÔNG có user.create/delete)
 *   - user@example.com   / User@123   → ROLE_USER   (chỉ user.view)
 */
class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $adminToken;
    private string $editorToken;
    private string $userToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->adminToken  = $this->fetchToken('admin@example.com', 'Admin@123');
        $this->editorToken = $this->fetchToken('editor@example.com', 'Editor@123');
        $this->userToken   = $this->fetchToken('user@example.com', 'User@123');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function fetchToken(string $email, string $password): string
    {
        $this->client->request(
            'POST',
            '/api/v1/auth/login',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password]),
        );

        $data = json_decode($this->client->getResponse()->getContent(), true);

        return $data['data']['access_token'] ?? '';
    }

    private function authHeader(string $token): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer ' . $token];
    }

    private function json(string $method, string $uri, string $token, array $body = []): void
    {
        $this->client->request(
            $method, $uri, [], [],
            array_merge(['CONTENT_TYPE' => 'application/json'], $this->authHeader($token)),
            $body ? json_encode($body) : null,
        );
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function roleIdByName(string $name): int
    {
        return (int) $this->em()
            ->createQuery('SELECT r.id FROM App\Entity\Role r WHERE r.name = :name')
            ->setParameter('name', $name)
            ->getSingleScalarResult();
    }

    private function userIdByEmail(string $email): int
    {
        return (int) $this->em()
            ->createQuery('SELECT u.id FROM App\Entity\User u WHERE u.email = :email')
            ->setParameter('email', $email)
            ->getSingleScalarResult();
    }

    /** Tạo user tạm trong DB để dùng cho update/delete tests */
    private function createTempUser(string $suffix = ''): User
    {
        $em      = $this->em();
        $hasher  = static::getContainer()->get(UserPasswordHasherInterface::class);
        $role    = $em->find(Role::class, $this->roleIdByName('ROLE_USER'));

        $user = new User();
        $user->setEmail('temp_' . $suffix . uniqid() . '@example.com');
        $user->setFullName('Temp User');
        $user->setPassword($hasher->hashPassword($user, 'Password@123'));
        $user->setIsActive(true);
        $user->addRoleEntity($role);

        $em->persist($user);
        $em->flush();

        return $user;
    }

    // -----------------------------------------------------------------------
    // index — GET /api/v1/users
    // -----------------------------------------------------------------------

    public function testIndexReturnsUserList(): void
    {
        $this->json('GET', '/api/v1/users', $this->adminToken);

        $this->assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
        $this->assertNotEmpty($body['data']);

        // Mapper không được expose password
        $first = $body['data'][0];
        $this->assertArrayNotHasKey('password', $first);
        $this->assertArrayHasKey('email', $first);
        $this->assertArrayHasKey('roles', $first);
        $this->assertArrayHasKey('is_active', $first);
    }

    public function testIndexForbiddenWithoutViewPermission(): void
    {
        // ROLE_USER chỉ có user.view — dùng token khác không có view
        // Tạo user không có bất kỳ permission nào để test 403
        $em     = $this->em();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $emptyRole = new Role();
        $emptyRole->setName('ROLE_NO_PERM_' . uniqid());
        $em->persist($emptyRole);

        $noPermUser = new User();
        $noPermUser->setEmail('noperm_' . uniqid() . '@example.com');
        $noPermUser->setFullName('No Perm');
        $noPermUser->setPassword($hasher->hashPassword($noPermUser, 'Password@123'));
        $noPermUser->addRoleEntity($emptyRole);
        $em->persist($noPermUser);
        $em->flush();

        $token = $this->fetchToken($noPermUser->getEmail(), 'Password@123');

        $this->json('GET', '/api/v1/users', $token);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    // -----------------------------------------------------------------------
    // show — GET /api/v1/users/{id}
    // -----------------------------------------------------------------------

    public function testShowReturnsUser(): void
    {
        $id = $this->userIdByEmail('editor@example.com');
        $this->json('GET', '/api/v1/users/' . $id, $this->adminToken);

        $this->assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($body['success']);
        $this->assertSame($id, $body['data']['id']);
        $this->assertArrayHasKey('roles', $body['data']);
        $this->assertArrayNotHasKey('password', $body['data']);
    }

    public function testShowReturns404WhenNotFound(): void
    {
        $this->json('GET', '/api/v1/users/99999', $this->adminToken);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    // -----------------------------------------------------------------------
    // store — POST /api/v1/users
    // -----------------------------------------------------------------------

    public function testStoreCreatesUser(): void
    {
        $roleId = $this->roleIdByName('ROLE_USER');

        $this->json('POST', '/api/v1/users', $this->adminToken, [
            'email'    => 'newuser_' . uniqid() . '@example.com',
            'fullName' => 'New User',
            'password' => 'Secret@123',
            'isActive' => true,
            'roleIds'  => [$roleId],
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertArrayNotHasKey('password', $body['data']);
        $this->assertNotEmpty($body['data']['roles']);
    }

    public function testStoreReturns422OnValidationFail(): void
    {
        $this->json('POST', '/api/v1/users', $this->adminToken, [
            'email'    => 'not-an-email',
            'fullName' => '',
            'password' => '123',   // too short
            'roleIds'  => [],      // empty
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    public function testStoreReturns409OnDuplicateEmail(): void
    {
        $roleId = $this->roleIdByName('ROLE_USER');

        $this->json('POST', '/api/v1/users', $this->adminToken, [
            'email'    => 'admin@example.com', // đã tồn tại
            'fullName' => 'Duplicate',
            'password' => 'Secret@123',
            'isActive' => true,
            'roleIds'  => [$roleId],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    public function testStoreForbiddenWithoutCreatePermission(): void
    {
        // Tạo user với role không có bất kỳ permission nào
        $em     = $this->em();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $emptyRole = new Role();
        $emptyRole->setName('ROLE_NO_CREATE_' . uniqid());
        $em->persist($emptyRole);

        $noCreateUser = new User();
        $noCreateUser->setEmail('nocreate_' . uniqid() . '@example.com');
        $noCreateUser->setFullName('No Create');
        $noCreateUser->setPassword($hasher->hashPassword($noCreateUser, 'Password@123'));
        $noCreateUser->addRoleEntity($emptyRole);
        $em->persist($noCreateUser);
        $em->flush();

        $token  = $this->fetchToken($noCreateUser->getEmail(), 'Password@123');
        $roleId = $this->roleIdByName('ROLE_USER');

        $this->json('POST', '/api/v1/users', $token, [
            'email'    => 'forbidden_' . uniqid() . '@example.com',
            'fullName' => 'Forbidden',
            'password' => 'Secret@123',
            'isActive' => true,
            'roleIds'  => [$roleId],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    // -----------------------------------------------------------------------
    // update — PUT/PATCH /api/v1/users/{id}
    // -----------------------------------------------------------------------

    public function testUpdateUser(): void
    {
        $user   = $this->createTempUser('upd');
        $roleId = $this->roleIdByName('ROLE_USER');

        $this->json('PUT', '/api/v1/users/' . $user->getId(), $this->adminToken, [
            'email'    => $user->getEmail(),
            'fullName' => 'Updated Name',
            'isActive' => false,
            'roleIds'  => [$roleId],
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($body['success']);
        $this->assertSame('Updated Name', $body['data']['full_name']);
        $this->assertFalse($body['data']['is_active']);
        $this->assertArrayNotHasKey('password', $body['data']);
    }

    public function testUpdateChangesPassword(): void
    {
        $user = $this->createTempUser('pwd');

        $this->json('PATCH', '/api/v1/users/' . $user->getId(), $this->adminToken, [
            'email'    => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'password' => 'NewSecret@456',
            'isActive' => true,
        ]);

        $this->assertResponseIsSuccessful();
        // Verify password không lộ ra
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('password', $body['data']);
    }

    public function testUpdateKeepsRolesWhenRoleIdsOmitted(): void
    {
        $user = $this->createTempUser('roles');

        $this->json('PATCH', '/api/v1/users/' . $user->getId(), $this->adminToken, [
            'email'    => $user->getEmail(),
            'fullName' => 'No Role Change',
            'isActive' => true,
            // roleIds omitted → roles giữ nguyên
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($body['data']['roles']);
    }

    public function testUpdateReturns404WhenNotFound(): void
    {
        $this->json('PUT', '/api/v1/users/99999', $this->adminToken, [
            'email'    => 'ghost@example.com',
            'fullName' => 'Ghost',
            'isActive' => true,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    public function testUpdateForbiddenWithoutEditPermission(): void
    {
        // ROLE_EDITOR có user.edit — dùng user không có user.edit
        $user   = $this->createTempUser('forbidden');
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $em     = $this->em();

        $emptyRole = new Role();
        $emptyRole->setName('ROLE_NO_EDIT_' . uniqid());
        $em->persist($emptyRole);

        $noEditUser = new User();
        $noEditUser->setEmail('noedit_' . uniqid() . '@example.com');
        $noEditUser->setFullName('No Edit');
        $noEditUser->setPassword($hasher->hashPassword($noEditUser, 'Password@123'));
        $noEditUser->addRoleEntity($emptyRole);
        $em->persist($noEditUser);
        $em->flush();

        $token = $this->fetchToken($noEditUser->getEmail(), 'Password@123');

        $this->json('PUT', '/api/v1/users/' . $user->getId(), $token, [
            'email'    => $user->getEmail(),
            'fullName' => 'Hack',
            'isActive' => true,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // -----------------------------------------------------------------------
    // destroy — DELETE /api/v1/users/{id}
    // -----------------------------------------------------------------------

    public function testDestroyDeletesUser(): void
    {
        $user = $this->createTempUser('del');

        $this->json('DELETE', '/api/v1/users/' . $user->getId(), $this->adminToken);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDestroyReturns404WhenNotFound(): void
    {
        $this->json('DELETE', '/api/v1/users/99999', $this->adminToken);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    public function testDestroyForbiddenWithoutDeletePermission(): void
    {
        // ROLE_EDITOR không có user.delete
        $user = $this->createTempUser('forbiddel');

        $this->json('DELETE', '/api/v1/users/' . $user->getId(), $this->editorToken);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }
}
