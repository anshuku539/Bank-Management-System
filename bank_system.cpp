#include <iostream>
#include <fstream>
#include <string>
#include <iomanip>
#include <limits>
#include <sstream>
#include <vector>
#include <cctype>

using namespace std;

class BankAccount {
private:
    int accountNumber;
    char holderName[100];
    char type;          // S = Savings, C = Current
    double balance;

public:

    void createAccount() {
        cout << "\nEnter Account Number: ";
        cin >> accountNumber;

        cin.ignore(numeric_limits<streamsize>::max(), '\n');

        cout << "Enter Account Holder Name: ";
        cin.getline(holderName, 100);

        cout << "Enter Account Type (S/C): ";
        cin >> type;
        type = toupper(type);

        cout << "Enter Initial Balance: ";
        cin >> balance;

        cout << "\nAccount Created Successfully!\n";
    }

    void showAccountDetails() const {
        cout << "Account Number: " << accountNumber << "\n";
        cout << "Account Holder: " << holderName << "\n";
        cout << "Account Type : " << (type == 'S' ? "Savings" : "Current") << "\n";
        cout << "Balance      : " << balance << "\n";
    }

    void deposit(double amount) {
        balance += amount;
        cout << "Amount Deposited. New Balance: " << balance << "\n";
    }

    void withdraw(double amount) {
        if (balance >= amount) {
            balance -= amount;
            cout << "Amount Withdrawn. New Balance: " << balance << "\n";
        } else {
            cout << "Insufficient Balance.\n";
        }
    }

    int getAccountNumber() const { return accountNumber; }
    double getBalance() const { return balance; }
    const char* getHolderName() const { return holderName; }
    char getType() const { return type; }
};



// ------------------------------
//      FILE HANDLING PART
// ------------------------------

void writeAccount() {
    BankAccount account;
    ofstream out("bank_accounts.txt", ios::app);

    if (!out) {
        cout << "File write error.\n";
        return;
    }

    account.createAccount();

    out << account.getAccountNumber() << '|'
        << account.getHolderName() << '|'
        << account.getType() << '|'
        << account.getBalance() << "\n";

    out.close();
}

void displayAllAccounts() {
    ifstream in("bank_accounts.txt");
    if (!in) {
        cout << "No account file found.\n";
        return;
    }

    cout << "\n\n\t\t--- ALL ACCOUNTS ---\n\n";
    cout << setw(10) << "A/c No" << setw(20) << "Name"
         << setw(10) << "Type" << setw(12) << "Balance\n";
    cout << string(55, '=') << "\n";

    string line;
    while (getline(in, line)) {
        if (line.empty()) continue;

        stringstream ss(line);
        string no, name, t, bal;

        getline(ss, no, '|');
        getline(ss, name, '|');
        getline(ss, t, '|');
        getline(ss, bal, '|');

        cout << setw(10) << stoi(no)
             << setw(20) << name
             << setw(10) << (t[0] == 'S' ? "Savings" : "Current")
             << setw(12) << stod(bal) << "\n";
    }
}

void displaySpecificAccount(int n) {
    ifstream in("bank_accounts.txt");
    if (!in) {
        cout << "Account file missing.\n";
        return;
    }

    string line;
    bool found = false;

    while (getline(in, line)) {
        if (line.empty()) continue;

        stringstream ss(line);
        string no, name, t, bal;

        getline(ss, no, '|');
        getline(ss, name, '|');
        getline(ss, t, '|');
        getline(ss, bal, '|');

        if (stoi(no) == n) {
            cout << "\n--- ACCOUNT DETAILS ---\n";
            cout << "Account Number: " << no << "\n";
            cout << "Account Holder: " << name << "\n";
            cout << "Account Type  : " << (t[0] == 'S' ? "Savings" : "Current") << "\n";
            cout << "Balance       : " << bal << "\n";
            found = true;
            break;
        }
    }

    if (!found)
        cout << "Account " << n << " not found.\n";
}

void depositWithdraw(int n, int option) {
    ifstream in("bank_accounts.txt");
    if (!in) {
        cout << "File error.\n";
        return;
    }

    vector<string> lines;
    string line;
    bool found = false;

    while (getline(in, line)) {
        if (line.empty()) {
            lines.push_back(line);
            continue;
        }

        stringstream ss(line);
        string no, name, t, bal;

        getline(ss, no, '|');
        getline(ss, name, '|');
        getline(ss, t, '|');
        getline(ss, bal, '|');

        int accNo = stoi(no);
        double balance = stod(bal);

        if (accNo == n) {
            found = true;
            double amt;

            if (option == 1) {
                cout << "Enter deposit amount: ";
                cin >> amt;
                balance += amt;
            }
            else if (option == 2) {
                cout << "Enter withdraw amount: ";
                cin >> amt;

                if (balance >= amt)
                    balance -= amt;
                else
                    cout << "Insufficient balance.\n";
            }

            stringstream updated;
            updated << accNo << "|" << name << "|" << t << "|" << balance;
            lines.push_back(updated.str());
        }
        else {
            lines.push_back(line);
        }
    }

    if (!found) {
        cout << "Account not found.\n";
        return;
    }

    ofstream out("bank_accounts.txt", ios::trunc);
    for (auto &l : lines) out << l << "\n";
}

void deleteAccount(int n) {
    ifstream in("bank_accounts.txt");
    if (!in) {
        cout << "File missing.\n";
        return;
    }

    vector<string> lines;
    string line;
    bool deleted = false;

    while (getline(in, line)) {
        if (line.empty()) continue;

        stringstream ss(line);
        string no;
        getline(ss, no, '|');

        if (stoi(no) == n) {
            deleted = true;
            continue;
        }
        lines.push_back(line);
    }

    if (!deleted) {
        cout << "Account not found.\n";
        return;
    }

    ofstream out("bank_accounts.txt", ios::trunc);
    for (auto &l : lines) out << l << "\n";

    cout << "\nAccount deleted successfully.\n";
}



// ------------------------------
//            MAIN MENU
// ------------------------------

int main() {
    int choice, accNum;

    do {
        cout << "\n\n\t--- BANK MANAGEMENT SYSTEM ---";
        cout << "\n1. Create New Account";
        cout << "\n2. Deposit Amount";
        cout << "\n3. Withdraw Amount";
        cout << "\n4. Check Account Details";
        cout << "\n5. View All Accounts";
        cout << "\n6. Close an Account";
        cout << "\n7. Exit";
        cout << "\n\nSelect Option (1-7): ";

        cin >> choice;

        switch (choice) {
            case 1: writeAccount(); break;
            case 2:
                cout << "Enter Account Number: ";
                cin >> accNum;
                depositWithdraw(accNum, 1);
                break;
            case 3:
                cout << "Enter Account Number: ";
                cin >> accNum;
                depositWithdraw(accNum, 2);
                break;
            case 4:
                cout << "Enter Account Number: ";
                cin >> accNum;
                displaySpecificAccount(accNum);
                break;
            case 5: displayAllAccounts(); break;
            case 6:
                cout << "Enter Account Number to Delete: ";
                cin >> accNum;
                deleteAccount(accNum);
                break;
            case 7:
                cout << "Thank you for using the system!\n";
                break;
            default:
                cout << "Invalid option. Try again.\n";
        }

        cin.ignore(numeric_limits<streamsize>::max(), '\n');
        cout << "\nPress Enter to continue...";
        cin.get();

    } while (choice != 7);

    return 0;
}
