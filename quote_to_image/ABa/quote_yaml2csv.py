#!/usr/bin/env python
from argparse import ArgumentParser
from csv import DictWriter
from pathlib import Path
import sys

import yaml


def get_args():
    parser = ArgumentParser(
        prog='quote_yaml2csv',
        description='Convert Literature Clock quotes from yaml to csv format.',
    )
    parser.add_argument('src', help='source yaml', type=Path)
    parser.add_argument('dst', help="destination csv (if omitted the source's .yaml ending will be replaced with .csv)",
                        nargs='?', type=Path)

    args = parser.parse_args()
    if args.dst is None:
        if args.src.suffix not in  ['.yaml', '.yml']:
            print("Error: cannot calculate dst (src has no .yaml|.yml ending)")
            sys.exit(1)
        args.dst = args.src.with_suffix('.csv')

    return args.src, args.dst


def read_yaml(src_file):
    with open(src_file, newline='\n', encoding="utf8") as yaml_file:
        result = yaml.safe_load(yaml_file)

    return result


def write_csv(content, dst_file):
    keys = []
    keys_unsorted = content[0].keys()
    for key in ['time', 'timestring', 'quote', 'title', 'author', 'sfw']:
        if key in keys_unsorted:
            keys.append(key)

    for key in keys_unsorted:
        if key not in keys:
            keys.append(key)

    with open(dst_file, 'w') as csv_file:
        writer = DictWriter(csv_file, keys, delimiter='|')
        writer.writeheader()
        for row in content:
            writer.writerow(row)


if __name__ == '__main__':
    src, dst = get_args()
    quotes = read_yaml(src)
    write_csv(quotes, dst)
