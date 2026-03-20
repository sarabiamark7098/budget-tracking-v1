import pandas as pd
import warnings
warnings.filterwarnings('ignore')

file_path = r'C:\Users\mtsarabia\Downloads\Budget Tracking.xlsx'

sheets_to_read = [
    "Cash In", "Expense", "Purchases", "Investments", "Stocks",
    "Crypto", "Utang (Dept)", "Utang Business", "Payment",
    "Insurance Plan", "MP2 Calculator", "Plan", "Budgeting"
]

xl = pd.ExcelFile(file_path)
print("=== ALL SHEETS IN FILE ===")
print(xl.sheet_names)
print()

pd.set_option('display.max_rows', None)
pd.set_option('display.max_columns', None)
pd.set_option('display.width', 300)
pd.set_option('display.max_colwidth', None)

for sheet in sheets_to_read:
    if sheet in xl.sheet_names:
        print(f"\n{'='*80}")
        print(f"SHEET: {sheet}")
        print(f"{'='*80}")
        df = pd.read_excel(file_path, sheet_name=sheet, header=None)
        print(f"Shape: {df.shape[0]} rows x {df.shape[1]} columns")
        print("\n--- RAW DATA (all rows, no header assumption) ---")
        print(df.to_string())
        print()
    else:
        print(f"\nWARNING: Sheet '{sheet}' NOT FOUND in file")
        print(f"Available sheets: {xl.sheet_names}")
