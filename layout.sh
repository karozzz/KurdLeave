#!/bin/bash

print_tree() {
    local dir="$1"
    local prefix="$2"
    local max_depth="$3"
    local current_depth="$4"

    if [ "$current_depth" -gt "$max_depth" ] && [ "$max_depth" -ne 0 ]; then
        return
    fi

    local files=()
    local dirs=()

    while IFS= read -r -d $'\0' entry; do
        if [ -d "$entry" ]; then
            dirs+=("$entry")
        else
            files+=("$entry")
        fi
    done < <(find "$dir" -maxdepth 1 -mindepth 1 -print0 | sort -z)

    for file in "${files[@]}"; do
        echo "$prefix|-- $(basename "$file")"
    done

    for directory in "${dirs[@]}"; do
        local dir_name=$(basename "$directory")
        if [[ "$dir_name" == "node_modules" || "$dir_name" == ".next" || "$dir_name" == ".git" ]]; then
            echo "$prefix|-- $dir_name/"
        else
            echo "$prefix|-- $dir_name/"
            print_tree "$directory" "$prefix|   " "$max_depth" $((current_depth + 1))
        fi
    done
}

max_depth=0
if [ $# -eq 1 ]; then
    max_depth=$1
fi

dir_name=$(basename "$PWD")
output=$(echo "$dir_name/"; print_tree "." "" "$max_depth" 1)

echo "$output"

# Copy to clipboard
echo "$output" | pbcopy

echo "Layout copied to clipboard."