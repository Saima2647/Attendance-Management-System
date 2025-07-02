# 📘 Attendance Management System

A web-based application designed to automate and manage student attendance in educational institutions. This system allows Admins, Teachers, and Students to log in and interact with the platform based on their role.

---

## 📌 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Requirements](https://github.com/Saima2647/Attendance-Management-System/edit/main/README.md#%EF%B8%8F-requirements)
- [User Roles](#-user-roles)
- [Security](#-security)
- [Testing Summary](#-testing-summary)
- [Future Scope](#-future-scope)
- [Screenshots](https://github.com/Saima2647/Attendance-Management-System/edit/main/README.md#%EF%B8%8F-screenshots)

---

## ✅ Features

- 🔐 Role-Based Login System: Admin, Teacher, and Student
- 📊 View & Export Attendance Reports (CSV, Excel, PDF, Print)
- 🧾 Subject-wise & Overall Attendance Calculation
- 📅 Class Scheduling via FullCalendar.js
- 📥 Assignment Upload and Subject Notes Download (for students)
- 🧑‍🏫 Admin Panel: Manage Users, Courses, Subjects, and Codes
- 🧠 Responsive UI using Bootstrap 5
- 📉 Real-time Dashboard Stats

---

## 🧑‍💻 Tech Stack

| Category     | Technology                          |
|--------------|-------------------------------------|
| Frontend     | HTML5, CSS3, JavaScript, Bootstrap  |
| Backend      | PHP (7.4+)                          |
| Database     | MySQL (5.7+), MariaDB               |
| Dev Server   | Apache via XAMPP                    |
| Libraries    | jQuery, FullCalendar.js, DataTables |

---

## ⚙️ Requirements

- PHP ≥ 7.4
- MySQL ≥ 5.7
- XAMPP / WAMP / LAMP
- Modern browser (Chrome, Firefox)

---

## 👥 User Roles
### 🔸Admin
- Add/manage users, courses, subjects
- Generate registration codes
- View/download attendance reports

### 🔸Teacher
- View calendar of scheduled classes
- Mark attendance
- View attendance records

### 🔸Student
- View attendance history
- Access class calendar
- Download notes and upload assignments

---

## 🔐 Security
- Passwords hashed using password_hash() with BCRYPT
- SQL Injection protection via Prepared Statements
- Session-based role validation and access restriction
- Server-side validation of user input

---

## 🧪 Testing Summary
- Module	Test Case	Result
- Login	Valid/invalid credentials	✅
- Admin Access	Add users, view reports	✅
- Teacher Access	Mark attendance, view data	✅
- Student Access	View reports, calendar access	✅
- Security	SQL injection attempt	✅ Prevented
- Integration	Role-based routing	✅

---

## 🚀 Future Scope
- Deploy system on cloud with online access
- Integrate facial recognition or biometric scanner
- Push notifications/SMS alerts to parents/students
- AI-based attendance analysis and prediction
- Mobile app for both students and faculty

---

## 🖼️ Screenshots

### Login Page
![image](https://github.com/user-attachments/assets/cbd4cc95-d58b-4ded-9fa2-700b258f8a55)

### Registration Page
![image](https://github.com/user-attachments/assets/8e60879d-fe5e-42d7-8cc7-3981a5f4ac40)

### Admin Dashboard
![image](https://github.com/user-attachments/assets/bd1076a7-6ba3-40bd-b399-17c6cb07a750)
 
### Attendance in Admin Panel
![image](https://github.com/user-attachments/assets/f3b839cd-9f2a-4aef-9079-b8500f2d3ea1)

### Teacher Dashboard
![image](https://github.com/user-attachments/assets/49d935a4-cf6f-4985-8c8a-563c405b8ee4)

### Teacher Subjects
![image](https://github.com/user-attachments/assets/e10a97e3-d327-4232-99b5-ccbe09a74e91)

### Attendance in Teachers Panel
![image](https://github.com/user-attachments/assets/eb1d8d42-7667-4a65-89ec-abe2e2bbd1e0)

### Attendance Page
![image](https://github.com/user-attachments/assets/ddedd6a9-4c66-4750-a3c1-2991909913d3)

### Student Dashboard
![image](https://github.com/user-attachments/assets/baacf1ee-d317-4a4d-aaf0-47eb9453e61b)

### Student Subjects
![image](https://github.com/user-attachments/assets/d0c83c59-12d3-4c14-a2e4-f4e07026f17d)

### Attendance in Student Panel
![image](https://github.com/user-attachments/assets/d2e0f3ae-e38c-49df-9e0d-4bce3dd9db83)



