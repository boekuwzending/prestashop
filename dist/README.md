PrestaShop has a requirement that each and every directory contains an index.php file that disallows directory browsing.

The GitHub Action step 'Copy the template "index.php" to all subdirectories' copies the index.php file from this directory into each output directory:

    find "temp-to-zip/boekuwzending/" -type d -exec cp "dist/index.php" {} \;