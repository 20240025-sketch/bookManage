<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            [
                'student_number' => '20240001',
                'name' => 'Tanaka Taro',
                'name_transcription' => 'tanaka taro',
                'email' => 'tanaka.taro@seiei.ac.jp',
                'class_number' => 1,
            ],
            [
                'student_number' => '20240002',
                'name' => 'Yamada Hanako',
                'name_transcription' => 'yamada hanako',
                'email' => 'yamada.hanako@seiei.ac.jp',
                'class_number' => 1,
            ],
            [
                'student_number' => '20240003',
                'name' => 'Satou Taro',
                'name_transcription' => 'satou taro',
                'email' => 'satou.taro@seiei.ac.jp',
                'class_number' => 2,
            ],
            [
                'student_number' => '20240004',
                'name' => 'Suzuki Misaki',
                'name_transcription' => 'suzuki misaki',
                'email' => 'suzuki.misaki@seiei.ac.jp',
                'class_number' => 2,
            ],
            [
                'student_number' => '20240005',
                'name' => 'Watanabe Ryuuichi',
                'name_transcription' => 'watanabe ryuuichi',
                'email' => 'watanabe.ryuuichi@seiei.ac.jp',
                'class_number' => 3,
            ],
            [
                'student_number' => '20240006',
                'name' => 'Inoue Yumi',
                'name_transcription' => 'inoue yumi',
                'email' => 'inoue.yumi@seiei.ac.jp',
                'class_number' => 3,
            ],
            [
                'student_number' => '20240007',
                'name' => 'Takahashi Kenta',
                'name_transcription' => 'takahashi kenta',
                'email' => 'takahashi.kenta@seiei.ac.jp',
                'class_number' => 4,
            ],
            [
                'student_number' => '20240008',
                'name' => 'Nakamura Megumi',
                'name_transcription' => 'nakamura megumi',
                'email' => 'nakamura.megumi@seiei.ac.jp',
                'class_number' => 4,
            ],
            [
                'student_number' => '20240009',
                'name' => 'Itou Shouta',
                'name_transcription' => 'itou shouta',
                'email' => 'itou.shouta@seiei.ac.jp',
                'class_number' => 5,
            ],
            [
                'student_number' => '20240010',
                'name' => 'Kobayashi Yui',
                'name_transcription' => 'kobayashi yui',
                'email' => 'kobayashi.yui@seiei.ac.jp',
                'class_number' => 5,
            ],
            [
                'student_number' => '20240011',
                'name' => 'Fujimoto Kouta',
                'name_transcription' => 'fujimoto kouta',
                'email' => 'fujimoto.kouta@seiei.ac.jp',
                'class_number' => 6,
            ],
            [
                'student_number' => '20240012',
                'name' => 'Katou Miyuu',
                'name_transcription' => 'katou miyuu',
                'email' => 'katou.miyuu@seiei.ac.jp',
                'class_number' => 6,
            ],
            [
                'student_number' => '20240013',
                'name' => 'Kimura Minami',
                'name_transcription' => 'kimura minami',
                'email' => 'kimura.minami@seiei.ac.jp',
                'class_number' => 7,
            ],
            [
                'student_number' => '20240014',
                'name' => 'Kawamura Kazuki',
                'name_transcription' => 'kawamura kazuki',
                'email' => 'kawamura.kazuki@seiei.ac.jp',
                'class_number' => 7,
            ],
            [
                'student_number' => '20240015',
                'name' => 'Murakami Yui',
                'name_transcription' => 'murakami yui',
                'email' => 'murakami.yui@seiei.ac.jp',
                'class_number' => 8,
            ],
        ];

        foreach ($students as $student) {
            Student::create($student);
        }
    }
}
