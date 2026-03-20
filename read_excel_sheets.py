import pandas as pd
import os
import warnings
warnings.filterwarnings('ignore')

file_path = r'C:\Users\mtsarabia\Downloads\Budget Tracking.xlsx'
output_dir = r'C:\laragon\www\budget-tracking-v1\excel_data'
os.makedirs(output_dir, exist_ok=True)

sheets_to_read = [
    "Cash In", "Expense", "Purchases", "Investments", "Stocks",
    "Crypto", "Utang (Dept)", "Utang Business", "Payment",
    "Insurance Plan", "MP2 Calculator", "Plan", "Budgeting"
]

xl = pd.ExcelFile(file_path)
print("=== ALL SHEETS IN FILE ===")
print(xl.sheet_names)

pd.set_option('display.max_rows', None)
pd.set_option('display.max_columns', None)
pd.set_option('display.width', 300)
pd.set_option('display.max_colwidth', None)

for sheet in sheets_to_read:
    safe_name = sheet.replace('(', '').replace(')', '').replace(' ', '_')
    out_file = os.path.join(output_dir, f"{safe_name}.txt")
    if sheet in xl.sheet_names:
        df = pd.read_excel(file_path, sheet_name=sheet, header=None)
        with open(out_file, 'w', encoding='utf-8') as f:
            f.write(f"SHEET: {sheet}\n")
            f.write(f"Shape: {df.shape[0]} rows x {df.shape[1]} columns\n\n")
            f.write(df.to_string())
            f.write('\n')
        print(f"Saved: {out_file}  ({df.shape[0]} rows x {df.shape[1]} cols)")
    else:
        print(f"WARNING: Sheet '{sheet}' NOT FOUND")

print("\nDone.")
