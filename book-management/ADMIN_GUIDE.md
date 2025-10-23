# 管理者権限ガイド

## 管理者の判定条件

このシステムでは、メールアドレスに基づいて管理者権限を自動的に判定します。

### 管理者になる条件

以下の**2つの条件を両方**満たす必要があります：

1. **ドメインが `seiei.ac.jp` であること**
   - `@seiei.ac.jp` で終わるメールアドレス

2. **メールアドレスのローカル部分（@の前）が数字から始まらないこと**
   - 文字（アルファベット）で始まること

### 判定例

#### ✅ 管理者として認識されるメールアドレス

| メールアドレス | 理由 |
|---|---|
| `tttttt@seiei.ac.jp` | ドメインが seiei.ac.jp、文字で始まる |
| `admin@seiei.ac.jp` | ドメインが seiei.ac.jp、文字で始まる |
| `teacher@seiei.ac.jp` | ドメインが seiei.ac.jp、文字で始まる |
| `library@seiei.ac.jp` | ドメインが seiei.ac.jp、文字で始まる |
| `staff001@seiei.ac.jp` | ドメインが seiei.ac.jp、文字で始まる |

#### ❌ 利用者として認識されるメールアドレス

| メールアドレス | 理由 |
|---|---|
| `20240001@seiei.ac.jp` | 数字で始まる（学生） |
| `1234567@seiei.ac.jp` | 数字で始まる（学生） |
| `2025001@seiei.ac.jp` | 数字で始まる（学生） |
| `admin@example.com` | ドメインが seiei.ac.jp でない |
| `teacher@gmail.com` | ドメインが seiei.ac.jp でない |
| `test@localhost` | ドメインが seiei.ac.jp でない |

## 管理者の自動登録機能

### 概要

管理者条件を満たすメールアドレスでログインを試みた場合、データベースにアカウントが存在しなくても**自動的に管理者アカウントが作成**されます。

### 動作フロー

1. **ログイン画面**でメールアドレスとパスワードを入力
2. システムがメールアドレスをチェック
3. 管理者条件を満たす場合：
   - データベースに存在しない → 自動的にアカウント作成
   - データベースに存在する → 通常のログイン処理
4. 利用者条件の場合：
   - データベースに存在しない → エラー（登録が必要）
   - データベースに存在する → 通常のログイン処理

### 自動作成されるアカウント情報

```php
email: 入力されたメールアドレス
name: メールアドレスの@前の部分（例: tttttt@seiei.ac.jp → tttttt）
student_number: 'ADMIN-' + タイムスタンプ（例: ADMIN-1698052800）
password: 入力されたパスワード（ハッシュ化）
```

## 管理者ができること

### 管理者専用機能

- 📊 **利用状況の確認** (`/usage-statistics`)
  - 貸出統計
  - 人気書籍ランキング
  - グラフ表示

- 📋 **貸出状況の管理** (`/borrow-status`)
  - 現在の貸出一覧
  - 滞納者の確認
  - 返却処理

- 📅 **図書当番の管理** (`/library-duty`)
  - 当番スケジュール
  - 記録の作成・編集

- 👥 **生徒一覧の閲覧** (`/students`)
  - 全生徒の情報表示
  - 生徒情報の編集

### 管理者・利用者共通機能

- 📚 **書籍の閲覧** (`/books`)
- 📖 **書籍の登録** (`/books/create`)
- 📤 **貸出登録** (`/borrows/create`)
- 💬 **本のリクエスト** (`/book-requests`)
- 🔔 **通知** (`/notifications`)

## 利用者ができること

### 利用者のアクセス制限

数字で始まるメールアドレス（例: `20240001@seiei.ac.jp`）は利用者として扱われ、以下の機能には**アクセスできません**：

- ❌ 利用状況
- ❌ 貸出状況の管理
- ❌ 図書当番の管理
- ❌ 全生徒の一覧（自分の情報のみ閲覧可能）

### 利用者が利用できる機能

- ✅ 書籍の閲覧
- ✅ 書籍の登録（自分で登録する場合）
- ✅ 貸出登録（自分が借りる場合）
- ✅ 本のリクエスト
- ✅ 通知の確認
- ✅ 自分の情報の閲覧

## 実装の詳細

### コード上の判定ロジック

#### Student.php - isAdmin() メソッド

```php
public function isAdmin(): bool
{
    // テストユーザー（ID=0）は管理者とする
    if ($this->id === 0) {
        return true;
    }
    
    if (!$this->email) {
        return false;
    }
    
    // @で分割
    $parts = explode('@', $this->email);
    if (count($parts) !== 2) {
        return false;
    }
    
    $localPart = $parts[0]; // @の前
    $domain = $parts[1];    // @の後
    
    // 条件1: ドメインが seiei.ac.jp であること
    if ($domain !== 'seiei.ac.jp') {
        return false;
    }
    
    // 条件2: メールアドレス（@の前の部分）が数字から始まらないこと
    $firstChar = substr($localPart, 0, 1);
    return !is_numeric($firstChar);
}
```

### フロントエンドでの権限チェック

#### App.vue

```javascript
// localStorageから権限情報を取得
const student = JSON.parse(localStorage.getItem('student') || '{}')
const isAdmin = student.isAdmin || false

// 管理者専用機能の表示制御
v-if="isAdmin"
```

## テストケース

### 管理者判定のテスト

```bash
php artisan tinker

# テストケース
$testCases = [
    'tttttt@seiei.ac.jp' => true,      // 管理者
    '1234567@seiei.ac.jp' => false,    // 利用者
    'admin@seiei.ac.jp' => true,       // 管理者
    'test@example.com' => false,       // 利用者
];

foreach ($testCases as $email => $expected) {
    $student = new App\Models\Student();
    $student->email = $email;
    $student->id = 999;
    
    $result = $student->isAdmin();
    $status = ($result === $expected) ? '✅' : '❌';
    
    echo "$status $email => " . ($result ? '管理者' : '利用者') . PHP_EOL;
}
```

## セキュリティ上の注意

### メールアドレスの検証

- システムは**メールアドレスのみ**で権限を判定します
- メールアドレスの偽装を防ぐため、認証システムを適切に設定してください
- 本番環境では、メール認証やSSOなどの追加の認証層を検討してください

### 管理者アカウントの管理

- 管理者アカウントは自動作成されるため、不正なアクセスを防ぐために：
  - 強力なパスワードポリシーを設定
  - ログインの試行回数を制限
  - ログの監視を実施

### ドメインの制限

現在は `seiei.ac.jp` のみが管理者ドメインとして設定されています。別のドメインを追加する場合は、`Student.php` の `isAdmin()` メソッドを修正してください。

## トラブルシューティング

### 管理者なのに権限がない

1. **メールアドレスを確認**
   - `@seiei.ac.jp` で終わっていますか？
   - 数字から始まっていませんか？

2. **ログインし直す**
   - 一度ログアウトして、再度ログインしてください
   - 権限情報はログイン時に設定されます

3. **localStorageをクリア**
   ```javascript
   localStorage.clear()
   location.reload()
   ```

### 利用者なのに管理者機能が見える

1. **ブラウザのキャッシュをクリア**
2. **localStorageの権限情報を確認**
   ```javascript
   console.log(JSON.parse(localStorage.getItem('student')))
   ```

### 管理者アカウントが自動作成されない

1. **メールアドレスの形式を確認**
   - 正しい形式: `name@seiei.ac.jp`
   - 数字で始まっていない: `admin@seiei.ac.jp` ✅, `1admin@seiei.ac.jp` ❌

2. **ログを確認**
   ```bash
   tail -f storage/logs/laravel.log | grep "Auto-created admin account"
   ```

## まとめ

- **管理者**: `文字@seiei.ac.jp` （例: `teacher@seiei.ac.jp`）
- **利用者**: `数字@seiei.ac.jp` または `any@other-domain` （例: `20240001@seiei.ac.jp`）
- 管理者は自動作成、利用者は事前登録が必要
- 権限はログイン時に判定され、localStorageに保存される
