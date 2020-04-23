<?php

namespace MingYuanYun\Push\Support;


class ArrayHelper
{
    // from https://github.com/yiisoft/yii2/blob/2.0.34/framework/helpers/BaseArrayHelper.php


    /**
     * 递归合并 2 个及以上的数组。
     * 如果每个数组元素有相同的字符串键值对，
     * 后者将会覆盖前者（不同于 array_merge_recursive）。
     * 如果两个数组都有数组类型的元素并且具有相同的键，
     * 那么将进行递归合并。
     * 对于整型键类型元素，后面数组中的元素将
     * 会被追加到前面的数组中去。
     * 你能够使用 [[UnsetArrayValue]] 对象从之前的数组中设置值或者
     * [[ReplaceArrayValue]] 强制替换原先的值来替代递归数组合并。
     * @param array $a 需要合并的数组
     * @param array $b 需要合并的数组。你能够指定额外的
     * 数组中的第三个参数，第四个参数等。
     * @return array 合并之后的数组（不改变原始数组。）
     */
    public static function merge($a, $b)
    {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            foreach (array_shift($args) as $k => $v) {
                if ($v instanceof UnsetArrayValue) {
                    unset($res[$k]);
                } elseif ($v instanceof ReplaceArrayValue) {
                    $res[$k] = $v->value;
                } elseif (is_int($k)) {
                    if (array_key_exists($k, $res)) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = static::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

    /**
     * 检索具有给定键或属性名的数组元素或对象属性的值。
     * 如果这个数组中不存在键，将返回默认值。
     * 从对象中获取值时不使用。
     *
     * 数组中的键可以指定圆点来检索子数组中的值或者对象中包含的属性。
     * 特别是，如果键是 `x.y.z`，
     * 然后返回的值中像这样 `$array['x']['y']['z']` 或者 `$array->x->y->z`（如果 `$array` 是一个对象）。
     * 如果 `$array['x']` 或者 `$array->x` 既不是数组也不是对象，将返回默认值。
     * 注意如果数组已经有元素 `x.y.z`，然后它的值将被返回来替代遍历子数组。
     * 因此最好要做指定键值对的数组
     * 像这样 `['x', 'y', 'z']`。
     *
     * 以下是一些用法示例,
     *
     * ```php
     * // working with array
     * $username = \yii\helpers\ArrayHelper::getValue($_POST, 'username');
     * // working with object
     * $username = \yii\helpers\ArrayHelper::getValue($user, 'username');
     * // working with anonymous function
     * $fullName = \yii\helpers\ArrayHelper::getValue($user, function ($user, $defaultValue) {
     *     return $user->firstName . ' ' . $user->lastName;
     * });
     * // using dot format to retrieve the property of embedded object
     * $street = \yii\helpers\ArrayHelper::getValue($users, 'address.street');
     * // using an array of keys to retrieve the value
     * $value = \yii\helpers\ArrayHelper::getValue($versions, ['1.0', 'date']);
     * ```
     *
     * @param array|object $array 从对象或数组中进行提取
     * @param string|\Closure|array $key 数组元素的键名，数组当中的键或者对象当中的属性名称，或者一个返回值的匿名函数。
     * 匿名函数应该像这样签名：
     * `function($array, $defaultValue)`。
     * 在 2.0.4 版本中可以通过数组当中可用的键来传递。
     * @param mixed $default 如果指定的数组当中的键不存在则返回默认值。
     * 从对象当中获取值时不使用。
     * @return mixed 找到该元素当中的值并返回，否则直接返回默认的值。
     */
    public static function getValue($array, $key, $default = null)
    {
        if ($key instanceof \Closure) {
            return $key($array, $default);
        }

        if (is_array($key)) {
            $lastKey = array_pop($key);
            foreach ($key as $keyPart) {
                $array = static::getValue($array, $keyPart);
            }
            $key = $lastKey;
        }

        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            return $array[$key];
        }

        if (($pos = strrpos($key, '.')) !== false) {
            $array = static::getValue($array, substr($key, 0, $pos), $default);
            $key = substr($key, $pos + 1);
        }

        if (is_object($array)) {
            // this is expected to fail if the property does not exist, or __get() is not implemented
            // it is not reliably possible to check whether a property is accessible beforehand
            return $array->$key;
        } elseif (is_array($array)) {
            return (isset($array[$key]) || array_key_exists($key, $array)) ? $array[$key] : $default;
        }

        return $default;
    }
}