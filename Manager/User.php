<?php

namespace Manager;

// Fix. Лучше привести всё к единому стилю, а то один метод статический два других нет
// Тут в либо всё сделать статическими, либо не делать их. В данном случае выбрал первый вариант
class User
{
    const limit = 10;

    /**
     * Возвращает пользователей старше заданного возраста.
     * @param int $ageFrom
     * @return array
     */
    public static function getUsers(int $ageFrom): array
    {
        // Fix: Лишнее, указывая тип в методе мы уже исключаем передачу строки(или другого типа) в параметре
        //$ageFrom = (int)trim($ageFrom);

        return \Gateway\User::getUsers($ageFrom);
    }

    /**
     * Возвращает пользователей по списку имен.
     * @param $names
     * @return array
     */
    // Fix 3. Так же логично привести данный метод с нижним. Оба работают с массивами
    // Но один принимает из $_GET другой в качестве параметров. 
    // Красивее и функциональнее в дальнейшем на мой взгляд будет сделать оба через параметры
    public static function getByNames(array $names): array
    {
        $users = [];
        // Fix 1. Массив может быть и не определён
        if (empty($names)) return $users;

        foreach ($names as $name) {

            // Fix 2. Поскольку пользователь может быть и не найден
            // То лучше не возвращать пустые значения
            if ($user = \Gateway\User::user($name)) {
                $users[] = $user;
            }
        }

        return $users;
    }

    /**
     * Добавляет пользователей в базу данных.
     * @param $users
     * @return array
     */
    // Fix. Поменял название метода, для единообразия с выше написанными, а также более соответствующего действию
    public static function addUsers($users): array
    {
        $idUsers = [];
        //Fix-1. Выносим commit из цикла так как он завершает транзакцию
        //Fix-2. Также всю конструкцию try/catch.
        //Fix-3. При пакетном добавлении, возвращать просто перечень ID без индетификации к кому они привязаны 
        // несколько не логично, ибо в дальнейшей потребуется привязка списка ID к пользователям. Предлагаю делать это сразу 
        \Gateway\User::getInstance()->beginTransaction();
        try {
            foreach ($users as $user) {
                \Gateway\User::add($user['name'], $user['lastName'], $user['age']);
                $idUsers[\Gateway\User::getInstance()->lastInsertId()] = $user;
            }
            \Gateway\User::getInstance()->commit();
        } catch (\Exception $e) {
            \Gateway\User::getInstance()->rollBack();
        }

        return $idUsers;
    }
}
