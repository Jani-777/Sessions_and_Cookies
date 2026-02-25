<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$rememberedUser = $_GET['remembered'] ?? '';

$errors = [];
$transaction_id = "";
$amount = "";
$category = "";
$transactions = [];

function loadTransactions() {
    if (file_exists('transactions.txt')) {
        $lines = file('transactions.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $transactions = [];
        foreach ($lines as $line) {
            [$tid, $amt, $cat] = explode('|', $line);
            $transactions[] = compact('tid', 'amt', 'cat');
        }
        return $transactions;
    }
    return [];
}

function saveTransaction($tid, $amt, $cat) {
    $line = "$tid|$amt|$cat\n";
    file_put_contents('transactions.txt', $line, FILE_APPEND);
}

$transactions = loadTransactions();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $transaction_id = htmlspecialchars($_POST["transaction_id"]);
    $amount = htmlspecialchars($_POST["amount"]);
    $category = htmlspecialchars($_POST["category"]);

    if (empty($transaction_id)) {
        $errors[] = "Transaction ID is required.";
    } elseif (!preg_match("/^[A-Za-z0-9]+$/", $transaction_id)) {
        $errors[] = "Transaction ID must be alphanumeric.";
    }

    if (empty($amount)) {
        $errors[] = "Amount is required.";
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $errors[] = "Amount must be a positive number.";
    }

    if (empty($category)) {
        $errors[] = "Category is required.";
    } elseif (!in_array($category, ["Income", "Expense", "Investment"])) {
        $errors[] = "Category must be Income, Expense, or Investment.";
    }

    if (empty($errors)) {
        saveTransaction($transaction_id, $amount, $category);
        header('Location: dashboard.php?transaction_id=' . urlencode($transaction_id));
        exit;
    }
}

if (isset($_GET['transaction_id'])) {
    $transaction_id = $_GET['transaction_id'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .dashboard-wrapper {
            background: linear-gradient(120deg, #fdf6e3 0%, #c9f1fc 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }

        .dashboard-main {
            max-width: 900px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 0 2rem;
        }

        .dashboard-section {
            background: #fff;
            padding: 2.2rem 2.8rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(24, 98, 230, 0.08);
        }

        .dashboard-section h3 {
            color: #2480ff;
            border-bottom: 2px solid #dbeafe;
            padding-bottom: 0.8rem;
            margin-bottom: 1.2rem;
        }

        .get-display-dashboard {
            background: #fffacd;
            padding: 0.8rem;
            border-radius: 5px;
            margin-top: 1rem;
            border-left: 4px solid #ffa500;
            font-family: monospace;
            font-size: 0.85rem;
            color: #666;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #222c38;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1.5px solid #dbeafe;
            border-radius: 7px;
            background: #f7fbff;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border: 1.5px solid #2480ff;
            outline: none;
            background: #eef7fc;
        }

        .form-group button {
            background: linear-gradient(90deg, #4f9cff 0%, #2480ff 100%);
            color: #fff;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-size: 1rem;
            letter-spacing: 0.5px;
            width: 100%;
            padding: 0.85rem;
            margin-top: 0.5rem;
            transition: background 0.2s;
            font-weight: 600;
            box-shadow: 0 2px 12px rgba(36,128,255,0.10);
        }

        .form-group button:hover {
            background: linear-gradient(90deg, #2480ff 0%, #4f9cff 100%);
        }

        .error-messages {
            background: #ffe6e6;
            border: 1px solid #ff4d4d;
            color: #b30000;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.2rem;
        }

        .error-messages ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .success-message {
            background: #e6ffe6;
            border: 1px solid #33cc33;
            color: #006600;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.2rem;
        }

        .result-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.2rem;
        }

        .result-table th,
        .result-table td {
            border: 1px solid #dbeafe;
            padding: 1rem;
            text-align: center;
        }

        .result-table th {
            background: #2480ff;
            color: #fff;
            font-weight: 600;
        }

        .result-table tr:nth-child(even) {
            background: #f7fbff;
        }

        .result-table tr:hover {
            background: #eef7fc;
        }

        .dashboard-header-section {
            grid-column: 1 / -1;
            background: linear-gradient(90deg, #2480ff 0%, #66a6ff 100%);
            color: #fff;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
        }

        .dashboard-header-section h2 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            letter-spacing: 1px;
        }

        .dashboard-header-section p {
            margin: 0.3rem 0;
            opacity: 0.95;
        }

        .action-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.2rem;
            flex-wrap: wrap;
        }

        .action-links a {
            display: inline-block;
            padding: 0.7rem 1.5rem;
            background: #fff;
            color: #2480ff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .action-links a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .action-links a.logout {
            background: linear-gradient(90deg, #ffaf7b 0%, #d76d77 100%);
            color: #fff;
        }

        @media (max-width: 768px) {
            .dashboard-main {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }

            .dashboard-section h3 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <div class="dashboard-main">
        <div class="dashboard-header-section">
            <h2>Welcome, <?=htmlspecialchars($_SESSION['username'])?>!</h2>
            <p>Financial Dashboard & Transaction Tracker</p>
            <?php if (isset($_COOKIE['remember_username'])): ?>
                <p><small>Remembered: <?=htmlspecialchars($_COOKIE['remember_username'])?></small></p>
            <?php endif; ?>
            <div class="action-links">
                <a href="search.php">🔍 Search Users</a>
                <a href="login.php" class="logout">🚪 Logout</a>
            </div>
        </div>

        <div class="dashboard-section">
            <h3>📊 Dashboard Info</h3>
            <p><strong>Username:</strong> <?=htmlspecialchars($_SESSION['username'])?></p>
            <p><strong>Active Session:</strong> Yes ✓</p>
            <p><strong>Total Transactions:</strong> <?=count($transactions)?></p>
            
            <?php if ($rememberedUser): ?>
                <div class="get-display-dashboard">
                    <strong>GET Parameter:</strong><br>
                    $_GET['remembered'] = "<?=htmlspecialchars($rememberedUser)?>"
                </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-section">
            <h3>💰 Add Transaction</h3>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="transaction_id">Transaction ID:</label>
                    <input type="text" name="transaction_id" value="<?=htmlspecialchars($transaction_id)?>" placeholder="e.g., TXN001">
                </div>

                <div class="form-group">
                    <label for="amount">Amount:</label>
                    <input type="text" name="amount" value="<?=htmlspecialchars($amount)?>" placeholder="e.g., 500">
                </div>

                <div class="form-group">
                    <label for="category">Category:</label>
                    <select name="category">
                        <option value="">--Select--</option>
                        <option value="Income" <?=$category=="Income" ? "selected" : ""?>>Income</option>
                        <option value="Expense" <?=$category=="Expense" ? "selected" : ""?>>Expense</option>
                        <option value="Investment" <?=$category=="Investment" ? "selected" : ""?>>Investment</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit">Submit Transaction</button>
                </div>

                 <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?=$error?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            </form>
        </div>

        <?php if (isset($_GET['transaction_id']) && empty($errors)): ?>
            <div class="dashboard-section" style="grid-column: 1 / -1;">
                <div class="success-message">
                    ✓ Transaction recorded successfully!
                </div>
            </div>
        <?php endif; ?>

        <?php if (count($transactions) > 0): ?>
            <div class="dashboard-section" style="grid-column: 1 / -1;">
                <h3>📋 Transaction History</h3>
                <table class="result-table">
                    <tr>
                        <th>Transaction ID</th>
                        <th>Amount</th>
                        <th>Category</th>
                    </tr>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?=htmlspecialchars($t['tid'])?></td>
                            <td>₱<?=htmlspecialchars($t['amt'])?></td>
                            <td><?=htmlspecialchars($t['cat'])?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
