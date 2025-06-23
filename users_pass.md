# 🏢 KurdLeave System - User Directory

## 👥 User Accounts Overview

_All users have been assigned the standard password: **`admin123`** for initial system access_

---

### 🔐 Role Hierarchy & Access Levels

| Level    | Role         | Description          | Access Rights                                          |
| -------- | ------------ | -------------------- | ------------------------------------------------------ |
| 🥇 **1** | **Admin**    | System Administrator | Full system control, user management, global settings  |
| 🥈 **2** | **Manager**  | Department Manager   | Department oversight, leave approvals, team management |
| 🥉 **3** | **Employee** | Regular Employee     | Personal leave requests, profile management            |

---

## 📋 Complete User Directory

### 🔴 **ADMINISTRATORS** (Level 1)

| Employee ID   | Name                     | Email                | Password      | Department      | Status    | Join Date  |
| ------------- | ------------------------ | -------------------- | ------------- | --------------- | --------- | ---------- |
| 🆔 **ADM001** | **System Administrator** | 📧 `admin@gmail.com` | 🔑 `admin123` | Human Resources | ✅ Active | 2023-01-01 |

### 🟡 **MANAGERS** (Level 2)

| Employee ID   | Name                | Email                     | Password      | Department      | Status    | Join Date  | Reports To |
| ------------- | ------------------- | ------------------------- | ------------- | --------------- | --------- | ---------- | ---------- |
| 🆔 **EMP010** | **Rawa Dara**       | 📧 `rawa@gmail.com`       | 🔑 `admin123` | Engineering     | ✅ Active | 2023-01-15 | Admin      |
| 🆔 **EMP030** | **Jane Smith**      | 📧 `jane.smith@gmail.com` | 🔑 `admin123` | Human Resources | ✅ Active | 2023-01-10 | Admin      |
| 🆔 **EMP040** | **David Miller**    | 📧 `david@gmail.com`      | 🔑 `admin123` | Finance         | ✅ Active | 2023-01-20 | Admin      |
| 🆔 **EMP050** | **Jennifer Wilson** | 📧 `jennifer@gmail.com`   | 🔑 `admin123` | Sales           | ✅ Active | 2023-01-25 | Admin      |

### 🟢 **EMPLOYEES** (Level 3)

| Employee ID   | Name              | Email                  | Password      | Department  | Status     | Join Date  | Reports To      |
| ------------- | ----------------- | ---------------------- | ------------- | ----------- | ---------- | ---------- | --------------- |
| 🆔 **EMP023** | **Michael Brown** | 📧 `michael@gmail.com` | 🔑 `admin123` | Engineering | ✅ Active  | 2023-02-01 | Rawa Dara       |
| 🆔 **EMP015** | **Karoz Rebaz**   | 📧 `karoz@gmail.com`   | 🔑 `admin123` | Engineering | ✅ Active  | 2023-02-15 | Rawa Dara       |
| 🆔 **EMP025** | **Aland Fryad**   | 📧 `aland@gmail.com`   | 🔑 `admin123` | Sales       | ⏳ Pending | 2023-04-28 | Jennifer Wilson |

---

## 📊 Department Summary

| Department             | Manager         | Total Users | Active | Pending |
| ---------------------- | --------------- | ----------- | ------ | ------- |
| 🏗️ **Engineering**     | Rawa Dara       | 3           | 3      | 0       |
| 👥 **Human Resources** | Jane Smith      | 2           | 2      | 0       |
| 💰 **Finance**         | David Miller    | 1           | 1      | 0       |
| 📈 **Sales**           | Jennifer Wilson | 2           | 1      | 1       |

---

## 🔒 Security Notes

- ⚠️ **Change default passwords immediately** after first login
- 🔐 Passwords should meet company security policy
- 📱 Two-factor authentication recommended for admin accounts
- 🕒 Regular password updates required every 90 days

---

_Last Updated: June 18, 2025_ | _Total Users: 8_ | _Active: 7_ | _Pending: 1_
