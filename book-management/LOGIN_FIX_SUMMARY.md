# ログインシステムの修正内容

## 問題

`20240025@seiei.ac.jp`（数字で始まるメールアドレス）で**管理者ログイン画面**からログインすると、管理者権限が付与されてしまっていた。

## 原因

### 旧実装の問題点

1. **AdminLogin.vue** - 管理者ログイン画面
   - 管理者パスワード（`0835385252`）を入力すれば、**どのメールアドレスでも**強制的に`isAdmin: true`に設定していた
   - バックエンドの権限判定を無視していた

2. **Login.vue** - 通常ログイン画面
   - 逆に、**どのメールアドレスでも**強制的に`isAdmin: false`に設定していた
   - 管理者条件を満たすメールアドレスでも利用者として扱われていた

### 旧コード（AdminLogin.vue）

```javascript
// ❌ 問題のあるコード
const adminPermissions = {
  ...permissions,
  isAdmin: true  // 管理者ログインでは必ずtrueに設定
}
```

### 旧コード（Login.vue）

```javascript
// ❌ 問題のあるコード
const userPermissions = {
  ...permissions,
  isAdmin: false  // 通常ログインでは必ずfalseに設定
}
```

## 修正内容

### 新実装の方針

**バックエンドの権限判定を信頼し、フロントエンドでは上書きしない**

### AdminLogin.vue - 修正後

```javascript
// ✅ 修正後のコード
const permissions = response.data.permissions || {}
const isActuallyAdmin = permissions.isAdmin === true

// 実際に管理者でない場合はエラー
if (!isActuallyAdmin) {
  error.value = 'このメールアドレスは管理者権限がありません。管理者条件: @seiei.ac.jp で数字から始まらないメールアドレス'
  loading.value = false
  return
}

// バックエンドから返された権限情報をそのまま使用
localStorage.setItem('userPermissions', JSON.stringify(permissions))
```

**変更点：**
- バックエンドの`permissions.isAdmin`をチェック
- 管理者でない場合はエラーメッセージを表示してログイン拒否
- 管理者の場合のみログイン成功

### Login.vue - 修正後

```javascript
// ✅ 修正後のコード
const permissions = response.data.permissions || {}
const isAdmin = permissions.isAdmin === true

// バックエンドから返された権限情報をそのまま使用
localStorage.setItem('isAdmin', isAdmin ? 'true' : 'false')
localStorage.setItem('userRole', isAdmin ? 'admin' : 'user')
localStorage.setItem('userPermissions', JSON.stringify(permissions))
```

**変更点：**
- バックエンドの`permissions.isAdmin`をそのまま使用
- 強制的に`false`にしない
- 管理者条件を満たすメールアドレスなら管理者としてログイン可能

## バックエンドの権限判定（変更なし）

### Student.php - isAdmin()メソッド

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

## テスト結果

### バックエンド判定テスト

```
=== 20240025@seiei.ac.jp ===
isAdmin: false (利用者) ✅

=== 他のテストケース ===
tttttt@seiei.ac.jp => 管理者 ✅
admin@seiei.ac.jp => 管理者 ✅
1234567@seiei.ac.jp => 利用者 ✅
```

### 期待される動作

#### 通常ログイン画面（/login）

| メールアドレス | パスワード | 結果 |
|---|---|---|
| `tttttt@seiei.ac.jp` | 正しいパスワード | ✅ 管理者としてログイン成功 |
| `20240025@seiei.ac.jp` | 正しいパスワード | ✅ 利用者としてログイン成功 |
| `admin@example.com` | 正しいパスワード | ✅ 利用者としてログイン成功 |

#### 管理者ログイン画面（/admin-login）

| 管理者パスワード | メールアドレス | パスワード | 結果 |
|---|---|---|---|
| `0835385252` | `tttttt@seiei.ac.jp` | 正しいパスワード | ✅ 管理者としてログイン成功 |
| `0835385252` | `20240025@seiei.ac.jp` | 正しいパスワード | ❌ エラー：管理者権限がありません |
| `0835385252` | `admin@example.com` | 正しいパスワード | ❌ エラー：管理者権限がありません |
| `wrong` | `tttttt@seiei.ac.jp` | 正しいパスワード | ❌ エラー：管理者パスワードが正しくありません |

## セキュリティの向上

### 修正前の問題点

- 管理者パスワード（`0835385252`）を知っていれば、**誰でも**管理者としてログインできた
- メールアドレスの制限がなかった

### 修正後の改善点

- 管理者パスワード **かつ** 管理者条件を満たすメールアドレスが必要
- 二重の認証が必要：
  1. 管理者パスワード（`0835385252`）
  2. 管理者条件を満たすメールアドレス（`xxx@seiei.ac.jp`、数字で始まらない）

## 推奨される使い方

### 管理者の場合

- **推奨**: 通常ログイン画面（`/login`）を使用
  - メールアドレスとパスワードだけでログイン可能
  - シンプルで使いやすい

- **非推奨**: 管理者ログイン画面（`/admin-login`）
  - 管理者パスワードも必要
  - 手間がかかる

### 利用者の場合

- **必須**: 通常ログイン画面（`/login`）を使用
  - 管理者ログイン画面は使用不可（エラーになる）

## まとめ

### 修正したファイル

1. `resources/js/pages/AdminLogin.vue` - バックエンドの権限判定をチェック、管理者でない場合はエラー
2. `resources/js/pages/Login.vue` - バックエンドの権限判定をそのまま使用

### 重要なポイント

- ✅ バックエンドの権限判定を信頼
- ✅ フロントエンドでは上書きしない
- ✅ 管理者ログイン画面でも権限チェックを実施
- ✅ セキュリティの向上

### 動作確認

```bash
# ビルド
npm run build

# テスト
# 1. 20240025@seiei.ac.jp で管理者ログイン → エラーになるはず
# 2. 20240025@seiei.ac.jp で通常ログイン → 利用者としてログイン成功
# 3. tttttt@seiei.ac.jp で管理者ログイン → 管理者としてログイン成功
# 4. tttttt@seiei.ac.jp で通常ログイン → 管理者としてログイン成功
```
