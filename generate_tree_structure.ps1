# This script generates a file containing the output of the `tree` command

# Define the output file path
$outputFile = "D:\Biblioteka\tree_structure.txt"

# Run the `tree` command and save the output to the file
# The `/F` flag includes files in the tree structure
# The `/A` flag uses ASCII characters for the tree structure
cmd.exe /c "tree /F /A > $outputFile"

# Notify the user
Write-Host "Tree structure saved to $outputFile"