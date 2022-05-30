<?php

namespace Gateway;

use PDO;

class User
{
    /**
     * @var PDO
     */
    public static $instance;

    /**
     * Реализация singleton
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (is_null(self::$instance)) {
            $dsn = 'mysql:dbname=db;host=127.0.0.1';
            $user = 'dbuser';
            $password = 'dbpass';
            self::$instance = new PDO($dsn, $user, $password);
        }

        return self::$instance;
    }

    /**
     * Возвращает список пользователей старше заданного возраста.
     * @param int $ageFrom
     * @return array
     */
    public static function getUsers(int $ageFrom): array
    {
        // Fix: Ковычки  для полей с названиями идетичными с командами SQL
        $stmt = self::getInstance()->prepare("SELECT id, `name`, lastName, `from`, age, settings FROM Users WHERE age > {$ageFrom} LIMIT " . \Manager\User::limit);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $users = [];
        foreach ($rows as $row) {
            // Fix: Принудительно говорим что результаты возвращать в ассоциативном массиве, ибо по дефолту возвращается объект
            $settings = !empty($row['settings']) ? json_decode($row['settings'], true) : false;
            $users[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'lastName' => $row['lastName'],
                'from' => $row['from'],
                'age' => $row['age'],
                // Fix: Поле может быть пустым, а также может не содержать ключа «key», чтобы не было «ворнингов» лучше всё проверять
                'key' => ($settings ? (isset($settings['key']) ? $settings['key'] : '') : ''),
            ];
        }

        return $users;
    }

    /**
     * Возвращает пользователя по имени.
     * @param string $name
     * @return array
     */
    // Fix. Для случаев когда не находим ничего, то надо либо добавить bool как возможное возвращаемое значение
    // Либо делать проверку и возвращать пустой массив. В данном случае на мой взгляд верное первое
    public static function user(string $name): array | bool
    {
        // Fix: Ковычки на поля и строковые ковычки на искомое значение
        $stmt = self::getInstance()->prepare("SELECT id, `name`, lastName, `from`, age FROM Users WHERE name = '{$name}'");
        $stmt->execute();
        $user_by_name = $stmt->fetch(PDO::FETCH_ASSOC);
        // Fix. Если не хотим возвращать settings то проще удалить его с запроса
        return $user_by_name;
    }

    /**
     * Добавляет пользователя в базу данных.
     * @param string $name
     * @param string $lastName
     * @param int $age
     * @return string
     */
    public static function add(string $name, string $lastName, int $age): string
    {
        // Fix. Замена позиции lastName и Age в Values
        $sth = self::getInstance()->prepare("INSERT INTO Users (name, lastName, age) VALUES (:name, :lastName, :age)");
        $sth->execute([':name' => $name, ':age' => $age, ':lastName' => $lastName]);

        return self::getInstance()->lastInsertId();
    }
}
