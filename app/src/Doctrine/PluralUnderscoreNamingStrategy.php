<?php

namespace App\Doctrine;

use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

/**
 * Tự động chuyển tên class Entity sang tên bảng số nhiều, snake_case.
 * Ví dụ: Permission -> permissions, UserRole -> user_roles
 *
 * Không cần khai báo #[ORM\Table(name: '...')] thủ công trên từng Entity nữa
 * (trừ khi muốn override khác với quy tắc số nhiều mặc định).
 */
class PluralUnderscoreNamingStrategy extends UnderscoreNamingStrategy
{
    private \Doctrine\Inflector\Inflector $inflector;

    public function __construct(int $case = CASE_LOWER)
    {
        parent::__construct($case);
        $this->inflector = InflectorFactory::create()->build();
    }

    public function classToTableName(string $className): string
    {
        // Lấy tên bảng snake_case từ class name (theo logic mặc định của cha)
        $tableName = parent::classToTableName($className);

        // Số nhiều hóa phần cuối cùng, ví dụ "user_role" -> "user_roles"
        return $this->inflector->pluralize($tableName);
    }
}
