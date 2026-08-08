<?php

namespace App\Tests;

use App\Entity\Permission;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test suite cho RoleController — cover 5 endpoint CRUD.
 *
 * Fixtures đã seed:
 *   - ROLE_ADMIN  (có toàn bộ permissions)
 *   - ROLE_EDITOR (chỉ user.view/create/edit — KHÔNG có role.*)
 *   - ROLE_USER   (chỉ user.view)
 *
 * Users:
 *   - admin@example.com  / Admin@123  → ROLE_ADMIN
 *   - editor@example.com / Editor@123 → ROLE_EDITOR
 *
 * Rule: mỗi test tự tạo client riêng, không dùng lại kernel đã boot.
 * Token được fetch 1 lần trong setUp() để tránh double-boot.
 */
class RoleControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $adminToken;
    private string $editorToken;

    protected function setUp(): void
    {
        // 1 client duy nhất cho toàn bộ test method — boot kernel 1 lần
        $this->client = static::createClient();
        $this->adminToken  = $this->fetchToken('admin@example.com', 'Admin@123');
        $this->editorToken = $this->fetchToken('editor@example.com', 'Editor@123');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function fetchToken(string $email, string $password): string
    {
        $this->client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
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
            $method,
            $uri,
            [],
            [],
            array_merge(['CONTENT_TYPE' => 'application/json'], $this->authHeader($token)),
            $body ? json_encode($body) : null,
        );
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function firstPermissionId(): int
    {
        return (int) $this->em()
            ->createQuery('SELECT p.id FROM App\Entity\Permission p ORDER BY p.id ASC')
            ->setMaxResults(1)
            ->getSingleScalarResult();
    }

    private function roleIdByName(string $name): int
    {
        return (int) $this->em()
            ->createQuery('SELECT r.id FROM App\Entity\Role r WHERE r.name = :name')
            ->setParameter('name', $name)
            ->getSingleScalarResult();
    }

    // -----------------------------------------------------------------------
    // index — GET /api/v1/roles
    // -----------------------------------------------------------------------

    public function testIndexReturnsRoleList(): void
    {
        $this->json('GET', '/api/v1/roles', $this->adminToken);

        $this->assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
        $this->assertNotEmpty($body['data']);
    }

    public function testIndexForbiddenWithoutViewPermission(): void
    {
        $this->json('GET', '/api/v1/roles', $this->editorToken);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    // -----------------------------------------------------------------------
    // show — GET /api/v1/roles/{id}
    // -----------------------------------------------------------------------

    public function testShowReturnsRoleWithPermissions(): void
    {
        $id = $this->roleIdByName('ROLE_ADMIN');
        $this->json('GET', '/api/v1/roles/' . $id, $this->adminToken);

        $this->assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertArrayHasKey('name', $body['data']);
        $this->assertArrayHasKey('permissions', $body['data']);
        $this->assertIsArray($body['data']['permissions']);
    }

    public function testShowReturns404WhenRoleNotFound(): void
    {
        $this->json('GET', '/api/v1/roles/99999', $this->adminToken);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    // -----------------------------------------------------------------------
    // store — POST /api/v1/roles
    // -----------------------------------------------------------------------

    public function testStoreCreatesRole(): void
    {
        $this->json('POST', '/api/v1/roles', $this->adminToken, [
            'name'          => 'ROLE_TEST_' . uniqid(),
            'description'   => 'Test role',
            'permissionIds' => [$this->firstPermissionId()],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $body = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertNotEmpty($body['data']['permissions']);
    }

    public function testStoreReturns422OnValidationFail(): void
    {
        $this->json('POST', '/api/v1/roles', $this->adminToken, [
            'name'          => '',  // NotBlank fail
            'permissionIds' => [],  // Count min:1 fail
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    public function testStoreReturns409OnDuplicateName(): void
    {
        $this->json('POST', '/api/v1/roles', $this->adminToken, [
            'name'          => 'ROLE_ADMIN', // đã tồn tại trong fixtures
            'permissionIds' => [$this->firstPermissionId()],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    public function testStoreForbiddenWithoutCreatePermission(): void
    {
        $this->json('POST', '/api/v1/roles', $this->editorToken, [
            'name'          => 'ROLE_TEST',
            'permissionIds' => [$this->firstPermissionId()],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    // -----------------------------------------------------------------------
    // update — PUT/PATCH /api/v1/roles/{id}
    // -----------------------------------------------------------------------

    public function testUpdateRole(): void
    {
        // Tạo role riêng để tránh làm bẩn fixture data
        $em   = $this->em();
        $role = new Role();
        $role->setName('ROLE_UPD_' . uniqid());
        $role->setDescription('before');
        $em->persist($role);
        $em->flush();

        $this->json('PUT', '/api/v1/roles/' . $role->getId(), $this->adminToken, [
            'name'          => $role->getName(),
            'description'   => 'after',
            'permissionIds' => [$this->firstPermissionId()],
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($body['success']);
        $this->assertSame('after', $body['data']['description']);
        $this->assertNotEmpty($body['data']['permissions']);
    }

    public function testUpdateKeepsPermissionsWhenPermissionIdsOmitted(): void
    {
        $em         = $this->em();
        $permission = $em->find(Permission::class, $this->firstPermissionId());

        $role = new Role();
        $role->setName('ROLE_PARTIAL_' . uniqid());
        $role->addPermission($permission);
        $em->persist($role);
        $em->flush();

        // PATCH không gửi permissionIds → permissions phải giữ nguyên
        $this->json('PATCH', '/api/v1/roles/' . $role->getId(), $this->adminToken, [
            'name' => $role->getName(),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($body['data']['permissions']);
    }

    public function testUpdateReturns404WhenNotFound(): void
    {
        $this->json('PUT', '/api/v1/roles/99999', $this->adminToken, [
            'name'          => 'ROLE_GHOST',
            'permissionIds' => [$this->firstPermissionId()],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    public function testUpdateForbiddenWithoutEditPermission(): void
    {
        $id = $this->roleIdByName('ROLE_USER');

        $this->json('PUT', '/api/v1/roles/' . $id, $this->editorToken, [
            'name'          => 'ROLE_USER',
            'permissionIds' => [$this->firstPermissionId()],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    // -----------------------------------------------------------------------
    // destroy — DELETE /api/v1/roles/{id}
    // -----------------------------------------------------------------------

    public function testDestroyDeletesRole(): void
    {
        $em   = $this->em();
        $role = new Role();
        $role->setName('ROLE_DEL_' . uniqid());
        $em->persist($role);
        $em->flush();
        $id = $role->getId();

        $this->json('DELETE', '/api/v1/roles/' . $id, $this->adminToken);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDestroyReturns404WhenNotFound(): void
    {
        $this->json('DELETE', '/api/v1/roles/99999', $this->adminToken);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    public function testDestroyForbiddenWithoutDeletePermission(): void
    {
        $id = $this->roleIdByName('ROLE_USER');

        $this->json('DELETE', '/api/v1/roles/' . $id, $this->editorToken);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }
}
