📌 POS System Login Module
A secure and modern Login System for your POS (Point Of Sale) application, built with PHP, Tailwind CSS, and MySQL.
It provides clean session-based authentication with professional, responsive UI.

⚡ Features
✅ Secure user authentication with hashed passwords
✅ Uses PDO prepared statements to prevent SQL injection
✅ Clean and professional Tailwind CSS UI
✅ Black login panel with white background for modern aesthetic
✅ Error handling with clear feedback
✅ Session management for logged-in users

🗂️ Project Structure
bash
Copy
Edit
/pos
 ├── includes/
 │    ├── db.php         # Database connection
 │    └── auth.php       # Auth functions (e.g., logout)
 ├── public/
 │    ├── login.php      # Login page
 │    ├── logout.php     # Logout script
 │    ├── dashboard.php  # Example protected page
 ├── README.md
⚙️ Installation
Clone this repository

bash
Copy
Edit
git clone https://github.com/AflalMohamed.git
Setup your database

Import your users table with columns:

pgsql
Copy
Edit
id (INT, PRIMARY KEY, AUTO_INCREMENT)
username (VARCHAR)
password_hash (VARCHAR)
role (VARCHAR)
Use password_hash() in PHP to create hashed passwords when inserting users.

Configure database connection

In includes/db.php:

php
Copy
Edit
<?php
$host = 'localhost';
$db   = 'pos_system';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
Update your credentials as needed.

Run it locally

Use XAMPP, MAMP, or any local PHP server:

bash
Copy
Edit
php -S localhost:8000 -t public
Then visit: http://localhost/pos.php

🚀 Usage
Enter your valid username and password.
admin(name)
admin123(password)   (db sql in includes folder)
On successful login, you’re redirected to dashboard.php.

Use logout.php to end the session securely.

🛡️ Security Best Practices
✅ Always store passwords with password_hash() and verify with password_verify()
✅ Use session_start() and properly unset sessions on logout
✅ Use HTTPS in production
✅ Validate & sanitize user input on the backend

🎨 Tech Stack
PHP: Server-side scripting

MySQL / MariaDB: Database

Tailwind CSS: Utility-first modern styling

HTML5

📄 License
This project is open-source. Feel free to use, modify, and adapt for your POS system.

🤝 Contributions
Pull requests are welcome! For major changes, open an issue first to discuss what you’d like to change.

✨ Author
Your Name — mohamedaflal154@gmail.com