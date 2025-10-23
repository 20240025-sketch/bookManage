<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Student;
use App\Models\Borrow;
use App\Models\BookRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSeeders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:generate-seeders {--tables=* : 特定のテーブルのみ生成}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '現在のデータベースデータからシーダーファイルを生成します';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = $this->option('tables');
        
        if (empty($tables)) {
            // すべてのテーブルを生成
            $tables = ['students', 'books', 'borrows', 'book_requests'];
        }
        
        $this->info('シーダーファイルを生成しています...');
        
        foreach ($tables as $table) {
            switch ($table) {
                case 'students':
                    $this->generateStudentSeeder();
                    break;
                case 'books':
                    $this->generateBookSeeder();
                    break;
                case 'borrows':
                    $this->generateBorrowSeeder();
                    break;
                case 'book_requests':
                    $this->generateBookRequestSeeder();
                    break;
                default:
                    $this->warn("未対応のテーブル: {$table}");
            }
        }
        
        $this->info('✅ シーダーファイルの生成が完了しました！');
        $this->info('実行するには: php artisan db:seed');
        
        return 0;
    }
    
    /**
     * 学生データのシーダーを生成
     */
    protected function generateStudentSeeder()
    {
        $students = Student::all();
        $count = $students->count();
        
        $this->info("📚 学生データ ({$count}件) を生成中...");
        
        $content = "<?php\n\nnamespace Database\\Seeders;\n\n";
        $content .= "use App\\Models\\Student;\n";
        $content .= "use Illuminate\\Database\\Seeder;\n\n";
        $content .= "class StudentSeeder extends Seeder\n{\n";
        $content .= "    public function run(): void\n    {\n";
        $content .= "        \$students = [\n";
        
        foreach ($students as $student) {
            $content .= "            [\n";
            $content .= "                'id' => {$student->id},\n";
            $content .= "                'student_id' => " . $this->formatValue($student->student_id) . ",\n";
            $content .= "                'name' => " . $this->formatValue($student->name) . ",\n";
            $content .= "                'class_name' => " . $this->formatValue($student->class_name) . ",\n";
            $content .= "                'student_number' => " . $this->formatValue($student->student_number) . ",\n";
            $content .= "                'email' => " . $this->formatValue($student->email) . ",\n";
            $content .= "                'password' => " . $this->formatValue($student->password) . ",\n";
            $content .= "            ],\n";
        }
        
        $content .= "        ];\n\n";
        $content .= "        foreach (\$students as \$student) {\n";
        $content .= "            Student::create(\$student);\n";
        $content .= "        }\n";
        $content .= "    }\n}\n";
        
        File::put(database_path('seeders/StudentSeeder.php'), $content);
        $this->info("✅ StudentSeeder.php を生成しました");
    }
    
    /**
     * 書籍データのシーダーを生成
     */
    protected function generateBookSeeder()
    {
        $books = Book::all();
        $count = $books->count();
        
        $this->info("📖 書籍データ ({$count}件) を生成中...");
        
        $content = "<?php\n\nnamespace Database\\Seeders;\n\n";
        $content .= "use App\\Models\\Book;\n";
        $content .= "use Illuminate\\Database\\Seeder;\n\n";
        $content .= "class BookSeeder extends Seeder\n{\n";
        $content .= "    public function run(): void\n    {\n";
        $content .= "        \$books = [\n";
        
        foreach ($books as $book) {
            $content .= "            [\n";
            $content .= "                'id' => {$book->id},\n";
            $content .= "                'title' => " . $this->formatValue($book->title) . ",\n";
            $content .= "                'author' => " . $this->formatValue($book->author) . ",\n";
            $content .= "                'publisher' => " . $this->formatValue($book->publisher) . ",\n";
            $content .= "                'isbn' => " . $this->formatValue($book->isbn) . ",\n";
            $content .= "                'classification' => " . $this->formatValue($book->classification) . ",\n";
            $content .= "                'registration_date' => " . $this->formatValue($book->registration_date) . ",\n";
            $content .= "            ],\n";
        }
        
        $content .= "        ];\n\n";
        $content .= "        foreach (\$books as \$book) {\n";
        $content .= "            Book::create(\$book);\n";
        $content .= "        }\n";
        $content .= "    }\n}\n";
        
        File::put(database_path('seeders/BookSeeder.php'), $content);
        $this->info("✅ BookSeeder.php を生成しました");
    }
    
    /**
     * 貸出データのシーダーを生成
     */
    protected function generateBorrowSeeder()
    {
        $borrows = Borrow::all();
        $count = $borrows->count();
        
        $this->info("📋 貸出データ ({$count}件) を生成中...");
        
        $content = "<?php\n\nnamespace Database\\Seeders;\n\n";
        $content .= "use App\\Models\\Borrow;\n";
        $content .= "use Illuminate\\Database\\Seeder;\n\n";
        $content .= "class BorrowSeeder extends Seeder\n{\n";
        $content .= "    public function run(): void\n    {\n";
        $content .= "        \$borrows = [\n";
        
        foreach ($borrows as $borrow) {
            $content .= "            [\n";
            $content .= "                'id' => {$borrow->id},\n";
            $content .= "                'book_id' => {$borrow->book_id},\n";
            $content .= "                'student_id' => {$borrow->student_id},\n";
            $content .= "                'borrowed_date' => " . $this->formatValue($borrow->borrowed_date) . ",\n";
            $content .= "                'return_due_date' => " . $this->formatValue($borrow->return_due_date) . ",\n";
            $content .= "                'returned_date' => " . $this->formatValue($borrow->returned_date) . ",\n";
            $content .= "            ],\n";
        }
        
        $content .= "        ];\n\n";
        $content .= "        foreach (\$borrows as \$borrow) {\n";
        $content .= "            Borrow::create(\$borrow);\n";
        $content .= "        }\n";
        $content .= "    }\n}\n";
        
        File::put(database_path('seeders/BorrowSeeder.php'), $content);
        $this->info("✅ BorrowSeeder.php を生成しました");
    }
    
    /**
     * 本のリクエストデータのシーダーを生成
     */
    protected function generateBookRequestSeeder()
    {
        $requests = BookRequest::all();
        $count = $requests->count();
        
        $this->info("📝 本のリクエストデータ ({$count}件) を生成中...");
        
        $content = "<?php\n\nnamespace Database\\Seeders;\n\n";
        $content .= "use App\\Models\\BookRequest;\n";
        $content .= "use Illuminate\\Database\\Seeder;\n\n";
        $content .= "class BookRequestSeeder extends Seeder\n{\n";
        $content .= "    public function run(): void\n    {\n";
        $content .= "        \$requests = [\n";
        
        foreach ($requests as $request) {
            $content .= "            [\n";
            $content .= "                'id' => {$request->id},\n";
            $content .= "                'student_id' => " . $this->formatValue($request->student_id) . ",\n";
            $content .= "                'title' => " . $this->formatValue($request->title) . ",\n";
            $content .= "                'author' => " . $this->formatValue($request->author) . ",\n";
            $content .= "                'reason' => " . $this->formatValue($request->reason) . ",\n";
            $content .= "                'status' => " . $this->formatValue($request->status) . ",\n";
            $content .= "                'admin_notes' => " . $this->formatValue($request->admin_notes) . ",\n";
            $content .= "            ],\n";
        }
        
        $content .= "        ];\n\n";
        $content .= "        foreach (\$requests as \$request) {\n";
        $content .= "            BookRequest::create(\$request);\n";
        $content .= "        }\n";
        $content .= "    }\n}\n";
        
        File::put(database_path('seeders/BookRequestSeeder.php'), $content);
        $this->info("✅ BookRequestSeeder.php を生成しました");
    }
    
    /**
     * 値を適切にフォーマット
     */
    protected function formatValue($value)
    {
        if (is_null($value)) {
            return 'null';
        }
        
        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        }
        
        return $value;
    }
}
