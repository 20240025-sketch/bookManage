<?php

namespace Database\Seeders;

use App\Models\BookRequest;
use Illuminate\Database\Seeder;

class BookRequestSeeder extends Seeder
{
    public function run(): void
    {
        $requests = [
            [
                'id' => 1,
                'student_id' => null,
                'title' => '鬼滅の刃',
                'author' => '吾峠, 呼世晴,吾峠呼世晴',
                'reason' => null,
                'status' => 'approved',
                'admin_notes' => null,
            ],
            [
                'id' => 3,
                'student_id' => null,
                'title' => 'チョコレート・ピース',
                'author' => '青山美智子',
                'reason' => null,
                'status' => 'approved',
                'admin_notes' => null,
            ],
            [
                'id' => 9,
                'student_id' => null,
                'title' => 'はたらく細胞',
                'author' => null,
                'reason' => null,
                'status' => 'approved',
                'admin_notes' => null,
            ],
            [
                'id' => 11,
                'student_id' => null,
                'title' => 'ｄ',
                'author' => 'ｄ',
                'reason' => null,
                'status' => 'rejected',
                'admin_notes' => null,
            ],
            [
                'id' => 12,
                'student_id' => null,
                'title' => 'g',
                'author' => null,
                'reason' => null,
                'status' => 'rejected',
                'admin_notes' => null,
            ],
            [
                'id' => 13,
                'student_id' => null,
                'title' => '鬼滅の刃',
                'author' => null,
                'reason' => null,
                'status' => 'approved',
                'admin_notes' => null,
            ],
            [
                'id' => 14,
                'student_id' => null,
                'title' => '銀魂',
                'author' => null,
                'reason' => null,
                'status' => 'pending',
                'admin_notes' => null,
            ],
            [
                'id' => 15,
                'student_id' => 1525,
                'title' => '銀魂',
                'author' => null,
                'reason' => null,
                'status' => 'pending',
                'admin_notes' => null,
            ],
            [
                'id' => 16,
                'student_id' => 1525,
                'title' => 'フランダースの犬',
                'author' => null,
                'reason' => null,
                'status' => 'pending',
                'admin_notes' => null,
            ],
            [
                'id' => 17,
                'student_id' => 1525,
                'title' => 'ハンターハンター',
                'author' => null,
                'reason' => null,
                'status' => 'pending',
                'admin_notes' => null,
            ],
        ];

        foreach ($requests as $request) {
            BookRequest::create($request);
        }
    }
}
