# Bank Management System

This repository contains a simple C++ console-based Bank Management System (`bank_system.cpp`).

Overview
- Simple account creation, deposit/withdraw, view, and delete operations.
- Stores data in `bank_accounts.txt` (one-line-per-account, `|`-delimited).

Build

Open PowerShell and run:

```powershell
g++ -std=c++17 -Wall -Wextra -O2 "bank_system.cpp" -o "bank_system.exe"
```

Run

```powershell
& "./bank_system.exe"
```

How to publish this repo to GitHub and GitHub Pages
1. Create a repository on GitHub named `Bank-Management-System` under your account `anshuku539` (or run the `gh` command below).
2. Push this local folder to the new remote (commands below).
3. In the repo Settings -> Pages, choose `main` branch and root (`/`) as source and save. After a few minutes your Pages site will be live at:

```
https://anshuku539.github.io/Bank-Management-System/
```

Quick commands (PowerShell)

```powershell
# initialize (if not already a git repo)
git init
git branch -M main
git add .
git commit -m "Initial commit: Bank Management System"
# add remote (replace if your repo exists)
git remote add origin https://github.com/anshuku539/Bank-Management-System.git
git push -u origin main
```

If you have GitHub CLI (`gh`) installed you can create+push in one step:

```powershell
gh repo create anshuku539/Bank-Management-System --public --source=. --remote=origin --push
```

Contact
If you want, I can: migrate any old binary data, add validation, or help run the push if you grant access or run the commands and paste errors here.
