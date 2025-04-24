import argparse
import random

def find_multiplier(modulo):
    candidate = random.randint(2, modulo - 1)
    return candidate

def main():
    parser = argparse.ArgumentParser(description="Generate a coprime multiplier.")
    parser.add_argument("modulo", type=int, help="Lowest flight number (if >1000, will assume this is the modulo)")
    args = parser.parse_args()

    if args.modulo > 1000:
        modulo = args.modulo
    else:
        modulo = 9999 - args.modulo + 1

    # Run a loop until multipler is 4211
    multiplier = find_multiplier(modulo)
    print(f"Multiplier: {multiplier}")
    print(f"Modulo: {modulo}")

if __name__ == "__main__":
    main()
