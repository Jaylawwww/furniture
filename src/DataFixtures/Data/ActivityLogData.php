<?php

declare(strict_types=1);

namespace App\DataFixtures\Data;

/**
 * Exported from local database activity_log table.
 */
final class ActivityLogData
{
    public static function rows(): array
    {
        return array (
  0 => 
  array (
    'id' => 1,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 12:25:34',
  ),
  1 => 
  array (
    'id' => 2,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'CREATE',
    'target_data' => 'Product: Table (ID: 1)',
    'date_time' => '2026-05-20 12:27:20',
  ),
  2 => 
  array (
    'id' => 3,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'CREATE',
    'target_data' => 'Category: Wood (ID: 1)',
    'date_time' => '2026-05-20 12:27:30',
  ),
  3 => 
  array (
    'id' => 4,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'CREATE',
    'target_data' => 'Category: Metal (ID: 2)',
    'date_time' => '2026-05-20 12:27:37',
  ),
  4 => 
  array (
    'id' => 5,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'CREATE',
    'target_data' => 'Category: Plastic (ID: 3)',
    'date_time' => '2026-05-20 12:27:43',
  ),
  5 => 
  array (
    'id' => 6,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'UPDATE',
    'target_data' => 'Product: Table (ID: 1)',
    'date_time' => '2026-05-20 12:27:55',
  ),
  6 => 
  array (
    'id' => 7,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'CREATE',
    'target_data' => 'Product: Chair (ID: 2)',
    'date_time' => '2026-05-20 12:28:20',
  ),
  7 => 
  array (
    'id' => 8,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGOUT',
    'target_data' => 'User logged out',
    'date_time' => '2026-05-20 12:28:26',
  ),
  8 => 
  array (
    'id' => 9,
    'user_id' => 2,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 12:28:54',
  ),
  9 => 
  array (
    'id' => 10,
    'user_id' => 2,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 12:29:02',
  ),
  10 => 
  array (
    'id' => 11,
    'user_id' => 2,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGOUT',
    'target_data' => 'User logged out',
    'date_time' => '2026-05-20 12:29:15',
  ),
  11 => 
  array (
    'id' => 12,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 12:33:44',
  ),
  12 => 
  array (
    'id' => 13,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 12:50:48',
  ),
  13 => 
  array (
    'id' => 14,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 12:50:49',
  ),
  14 => 
  array (
    'id' => 15,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 12:52:50',
  ),
  15 => 
  array (
    'id' => 16,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'DELETE',
    'target_data' => 'User: jelorence07@gmail.com (ID: 2)',
    'date_time' => '2026-05-20 14:20:43',
  ),
  16 => 
  array (
    'id' => 17,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:21:19',
  ),
  17 => 
  array (
    'id' => 18,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:21:19',
  ),
  18 => 
  array (
    'id' => 19,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:21:20',
  ),
  19 => 
  array (
    'id' => 20,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:22:02',
  ),
  20 => 
  array (
    'id' => 21,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:22:13',
  ),
  21 => 
  array (
    'id' => 22,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:27:36',
  ),
  22 => 
  array (
    'id' => 23,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:29:59',
  ),
  23 => 
  array (
    'id' => 24,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:30:55',
  ),
  24 => 
  array (
    'id' => 25,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:30:58',
  ),
  25 => 
  array (
    'id' => 26,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:32:07',
  ),
  26 => 
  array (
    'id' => 27,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:58:01',
  ),
  27 => 
  array (
    'id' => 28,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:58:04',
  ),
  28 => 
  array (
    'id' => 29,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:58:12',
  ),
  29 => 
  array (
    'id' => 30,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 14:58:13',
  ),
  30 => 
  array (
    'id' => 31,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 15:05:55',
  ),
  31 => 
  array (
    'id' => 32,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 15:05:57',
  ),
  32 => 
  array (
    'id' => 33,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-20 15:43:44',
  ),
  33 => 
  array (
    'id' => 34,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 01:31:21',
  ),
  34 => 
  array (
    'id' => 35,
    'user_id' => 5,
    'username' => 'carmeli.biscocho@lsu.edu.ph',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 05:27:45',
  ),
  35 => 
  array (
    'id' => 36,
    'user_id' => 5,
    'username' => 'carmeli.biscocho@lsu.edu.ph',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 05:28:00',
  ),
  36 => 
  array (
    'id' => 37,
    'user_id' => 5,
    'username' => 'carmeli.biscocho@lsu.edu.ph',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 05:28:07',
  ),
  37 => 
  array (
    'id' => 38,
    'user_id' => 9,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 06:08:04',
  ),
  38 => 
  array (
    'id' => 39,
    'user_id' => 12,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 06:26:31',
  ),
  39 => 
  array (
    'id' => 40,
    'user_id' => 12,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGOUT',
    'target_data' => 'User logged out',
    'date_time' => '2026-05-21 06:26:38',
  ),
  40 => 
  array (
    'id' => 41,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 06:40:05',
  ),
  41 => 
  array (
    'id' => 42,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'DELETE',
    'target_data' => 'User: calibscch@gmail.com (ID: 14)',
    'date_time' => '2026-05-21 06:40:22',
  ),
  42 => 
  array (
    'id' => 43,
    'user_id' => 16,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:30:11',
  ),
  43 => 
  array (
    'id' => 44,
    'user_id' => 16,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGOUT',
    'target_data' => 'User logged out',
    'date_time' => '2026-05-21 07:30:18',
  ),
  44 => 
  array (
    'id' => 45,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:31:19',
  ),
  45 => 
  array (
    'id' => 46,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGOUT',
    'target_data' => 'User logged out',
    'date_time' => '2026-05-21 07:31:27',
  ),
  46 => 
  array (
    'id' => 47,
    'user_id' => 17,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:32:17',
  ),
  47 => 
  array (
    'id' => 48,
    'user_id' => 17,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:32:28',
  ),
  48 => 
  array (
    'id' => 49,
    'user_id' => 17,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGOUT',
    'target_data' => 'User logged out',
    'date_time' => '2026-05-21 07:32:31',
  ),
  49 => 
  array (
    'id' => 50,
    'user_id' => 18,
    'username' => 'cursorrnine@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:36:21',
  ),
  50 => 
  array (
    'id' => 51,
    'user_id' => 20,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:42:28',
  ),
  51 => 
  array (
    'id' => 52,
    'user_id' => 23,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:53:49',
  ),
  52 => 
  array (
    'id' => 53,
    'user_id' => 23,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:53:59',
  ),
  53 => 
  array (
    'id' => 54,
    'user_id' => 24,
    'username' => 'cursorrnine@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:55:03',
  ),
  54 => 
  array (
    'id' => 55,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:55:47',
  ),
  55 => 
  array (
    'id' => 56,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'CREATE',
    'target_data' => 'Product: Door (ID: 3)',
    'date_time' => '2026-05-21 07:56:47',
  ),
  56 => 
  array (
    'id' => 57,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:57:35',
  ),
  57 => 
  array (
    'id' => 58,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:57:41',
  ),
  58 => 
  array (
    'id' => 59,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:57:45',
  ),
  59 => 
  array (
    'id' => 60,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:58:25',
  ),
  60 => 
  array (
    'id' => 61,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:58:37',
  ),
  61 => 
  array (
    'id' => 62,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 07:59:04',
  ),
  62 => 
  array (
    'id' => 63,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 08:00:54',
  ),
  63 => 
  array (
    'id' => 64,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 08:01:59',
  ),
  64 => 
  array (
    'id' => 65,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 08:03:24',
  ),
  65 => 
  array (
    'id' => 66,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 08:04:54',
  ),
  66 => 
  array (
    'id' => 67,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'CREATE',
    'target_data' => 'Category: Silicone (ID: 4)',
    'date_time' => '2026-05-21 08:05:47',
  ),
  67 => 
  array (
    'id' => 68,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 08:05:56',
  ),
  68 => 
  array (
    'id' => 69,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 08:07:29',
  ),
  69 => 
  array (
    'id' => 70,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 08:42:18',
  ),
  70 => 
  array (
    'id' => 71,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 08:42:46',
  ),
  71 => 
  array (
    'id' => 72,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 08:42:52',
  ),
  72 => 
  array (
    'id' => 73,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 08:42:59',
  ),
  73 => 
  array (
    'id' => 74,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 09:57:29',
  ),
  74 => 
  array (
    'id' => 75,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 09:57:48',
  ),
  75 => 
  array (
    'id' => 76,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 16:58:12',
  ),
  76 => 
  array (
    'id' => 77,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'CREATE',
    'target_data' => 'User: calibscch@gmail.com (ID: 25)',
    'date_time' => '2026-05-21 16:59:15',
  ),
  77 => 
  array (
    'id' => 78,
    'user_id' => 1,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGOUT',
    'target_data' => 'User logged out',
    'date_time' => '2026-05-21 16:59:35',
  ),
  78 => 
  array (
    'id' => 79,
    'user_id' => 25,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 16:59:45',
  ),
  79 => 
  array (
    'id' => 80,
    'user_id' => 25,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'DELETE',
    'target_data' => 'Product: Table (ID: 1)',
    'date_time' => '2026-05-21 16:59:59',
  ),
  80 => 
  array (
    'id' => 81,
    'user_id' => 25,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'DELETE',
    'target_data' => 'Category: Silicone (ID: 4)',
    'date_time' => '2026-05-21 17:00:13',
  ),
  81 => 
  array (
    'id' => 82,
    'user_id' => 25,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'DELETE',
    'target_data' => 'User: admin@gmail.com (ID: 1)',
    'date_time' => '2026-05-21 17:00:24',
  ),
  82 => 
  array (
    'id' => 83,
    'user_id' => 25,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGOUT',
    'target_data' => 'User logged out',
    'date_time' => '2026-05-21 17:02:17',
  ),
  83 => 
  array (
    'id' => 84,
    'user_id' => 26,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:02:31',
  ),
  84 => 
  array (
    'id' => 85,
    'user_id' => 26,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'CREATE',
    'target_data' => 'User: calibscch@gmail.com (ID: 27)',
    'date_time' => '2026-05-21 17:02:52',
  ),
  85 => 
  array (
    'id' => 86,
    'user_id' => 26,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGOUT',
    'target_data' => 'User logged out',
    'date_time' => '2026-05-21 17:03:18',
  ),
  86 => 
  array (
    'id' => 87,
    'user_id' => 27,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:03:27',
  ),
  87 => 
  array (
    'id' => 88,
    'user_id' => 27,
    'username' => 'calibscch@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGOUT',
    'target_data' => 'User logged out',
    'date_time' => '2026-05-21 17:03:53',
  ),
  88 => 
  array (
    'id' => 89,
    'user_id' => 26,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:04:00',
  ),
  89 => 
  array (
    'id' => 90,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:19:16',
  ),
  90 => 
  array (
    'id' => 91,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:19:41',
  ),
  91 => 
  array (
    'id' => 92,
    'user_id' => 26,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:33:12',
  ),
  92 => 
  array (
    'id' => 93,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:37:29',
  ),
  93 => 
  array (
    'id' => 94,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:40:46',
  ),
  94 => 
  array (
    'id' => 95,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:42:52',
  ),
  95 => 
  array (
    'id' => 96,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:44:02',
  ),
  96 => 
  array (
    'id' => 97,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:44:15',
  ),
  97 => 
  array (
    'id' => 98,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:45:11',
  ),
  98 => 
  array (
    'id' => 99,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:46:14',
  ),
  99 => 
  array (
    'id' => 100,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:48:45',
  ),
  100 => 
  array (
    'id' => 101,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:50:25',
  ),
  101 => 
  array (
    'id' => 102,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:50:48',
  ),
  102 => 
  array (
    'id' => 103,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:55:44',
  ),
  103 => 
  array (
    'id' => 104,
    'user_id' => 3,
    'username' => 'jelorence07@gmail.com',
    'role' => 'ROLE_STAFF',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 17:55:44',
  ),
  104 => 
  array (
    'id' => 105,
    'user_id' => 26,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGIN',
    'target_data' => 'User logged in',
    'date_time' => '2026-05-21 18:32:17',
  ),
  105 => 
  array (
    'id' => 106,
    'user_id' => 26,
    'username' => 'admin@gmail.com',
    'role' => 'ROLE_ADMIN',
    'action' => 'LOGOUT',
    'target_data' => 'User logged out',
    'date_time' => '2026-05-21 18:32:23',
  ),
);
    }
}
