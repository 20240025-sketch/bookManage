# データベースシーダー使用ガイド

## 概要

このシステムでは、現在のデータベースデータからシーダーファイルを自動生成できます。これにより、本番環境のデータを開発環境やテスト環境に簡単に複製できます。

## シーダーファイルの生成

### すべてのテーブルのシーダーを生成

```bash
php artisan db:generate-seeders
```

以下のシーダーファイルが生成されます：
- `database/seeders/StudentSeeder.php` - 学生データ (528件)
- `database/seeders/BookSeeder.php` - 書籍データ (29件)
- `database/seeders/BorrowSeeder.php` - 貸出データ (925件)
- `database/seeders/BookRequestSeeder.php` - 本のリクエストデータ (10件)

### 特定のテーブルのみ生成

```bash
# 学生データのみ
php artisan db:generate-seeders --tables=students

# 書籍と貸出データのみ
php artisan db:generate-seeders --tables=books --tables=borrows
```

利用可能なテーブル：
- `students` - 学生データ
- `books` - 書籍データ
- `borrows` - 貸出データ
- `book_requests` - 本のリクエストデータ

## シーダーの実行

### すべてのシーダーを実行

```bash
php artisan db:seed
```

以下の順序で実行されます：
1. SchoolClassSeeder - クラス情報
2. StudentSeeder - 学生データ
3. BookSeeder - 書籍データ
4. BorrowSeeder - 貸出データ
5. BookRequestSeeder - 本のリクエストデータ

### 特定のシーダーのみ実行

```bash
# 学生データのみ投入
php artisan db:seed --class=StudentSeeder

# 書籍データのみ投入
php artisan db:seed --class=BookSeeder

# 貸出データのみ投入
php artisan db:seed --class=BorrowSeeder

# 本のリクエストデータのみ投入
php artisan db:seed --class=BookRequestSeeder
```

## データベースの完全リセット

### データベースを空にして再投入

```bash
# マイグレーションをロールバックしてシーダーを実行
php artisan migrate:fresh --seed
```

⚠️ **警告**: このコマンドはすべてのデータを削除します！

### 特定のテーブルのみクリア

```bash
# Tinkerで特定のテーブルをクリア
php artisan tinker

# 例: 貸出データをクリア
App\Models\Borrow::truncate();

# 例: 本のリクエストをクリア
App\Models\BookRequest::truncate();
```

## 使用例

### シナリオ1: 本番データを開発環境にコピー

```bash
# 1. 本番環境で現在のデータからシーダーを生成
php artisan db:generate-seeders

# 2. 生成されたシーダーファイルをGitにコミット
git add database/seeders/
git commit -m "Update seeders with production data"
git push

# 3. 開発環境でプル
git pull

# 4. データベースをリセットしてシーダーを実行
php artisan migrate:fresh --seed
```

### シナリオ2: 学生データのみ更新

```bash
# 1. 学生データのシーダーを生成
php artisan db:generate-seeders --tables=students

# 2. 既存の学生データをクリア
php artisan tinker
App\Models\Student::truncate();
exit

# 3. 新しい学生データを投入
php artisan db:seed --class=StudentSeeder
```

### シナリオ3: テストデータの準備

```bash
# 1. 現在のデータからシーダーを生成
php artisan db:generate-seeders

# 2. 別のブランチで作業
git checkout -b test-environment

# 3. いつでもデータをリセット可能
php artisan migrate:fresh --seed
```

## 注意事項

### ID の保持

生成されたシーダーは元のデータのIDを保持します。これにより：
- ✅ リレーションシップが維持される
- ✅ 外部キー制約が正しく機能する
- ⚠️ 既存のIDと競合する可能性がある

### 大量データの処理

貸出データ（925件）など大量のデータがある場合：
- シーダーファイルのサイズが大きくなります
- 実行に時間がかかる場合があります
- 必要に応じて分割することを検討してください

### パスワードのセキュリティ

StudentSeederにはハッシュ化されたパスワードが含まれています：
- ✅ 平文のパスワードは含まれていません
- ✅ bcryptハッシュがそのままコピーされます
- ⚠️ 本番環境のパスワードハッシュを開発環境で使用することに注意

## トラブルシューティング

### エラー: Integrity constraint violation

外部キー制約エラーが発生する場合、正しい順序でシーダーを実行してください：

```bash
php artisan db:seed --class=SchoolClassSeeder
php artisan db:seed --class=StudentSeeder
php artisan db:seed --class=BookSeeder
php artisan db:seed --class=BorrowSeeder
php artisan db:seed --class=BookRequestSeeder
```

または、DatabaseSeeder.phpで定義された順序で実行：

```bash
php artisan db:seed
```

### エラー: Undefined type 'StudentSeeder'

シーダーファイルがまだ生成されていません：

```bash
php artisan db:generate-seeders
```

### シーダーの再生成

データが更新された場合、再度シーダーを生成してください：

```bash
# 古いシーダーファイルは上書きされます
php artisan db:generate-seeders
```

## まとめ

- **データのバックアップ**: `php artisan db:generate-seeders`
- **データの投入**: `php artisan db:seed`
- **完全リセット**: `php artisan migrate:fresh --seed`
- **部分更新**: `--class=` オプションで特定のシーダーのみ実行
