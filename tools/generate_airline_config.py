import argparse
import random
from math import gcd

def is_valid_multiplier(candidate, modulo):
    return gcd(candidate, modulo) == 1

def find_valid_multipliers(modulo, count=10):
    found = set()
    while len(found) < count:
        candidate = random.randint(2, modulo - 1)
        if is_valid_multiplier(candidate, modulo):
            found.add(candidate)
    return sorted(found)

def main():
    parser = argparse.ArgumentParser(description="Generate a coprime multiplier.")
    parser.add_argument("modulo", type=int, help="Lowest flight number (if >1000, will assume this is the modulo)")
    parser.add_argument("--count", type=int, default=10, help="Number of valid multipliers to generate")
    args = parser.parse_args()

    if args.modulo > 1000:
        modulo = args.modulo
    else:
        modulo = 80784 - args.modulo + 1

    multipliers = find_valid_multipliers(modulo, args.count)

    print(f"Modulo: {modulo}")
    print("Valid multipliers:")
    for m in multipliers:
        print(f"  - {m}")

if __name__ == "__main__":
    main()
