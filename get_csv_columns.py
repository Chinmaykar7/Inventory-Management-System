import pandas as pd
with open('csv_columns.txt', 'w', encoding='utf-8') as f:
    for file in ['Transaction Master.csv', 'Lost Table.csv', 'Waste Table.csv', 'Stock Master.csv']:
        try:
            df = pd.read_csv(file, nrows=1)
            f.write(f"{file}:\n{list(df.columns)}\n\n")
        except Exception as e:
            f.write(f"Error reading {file}: {e}\n\n")
