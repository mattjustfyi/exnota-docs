# Hashing

The `sha` returned from GitHub for a file is the hash used by Git, which can also be retrieved via:

```bash
git ls-files -s tests/Data/Docs/OneLevel/Flutter-Self-Service-Index.md
```
It can be generated via:

```bash
git hash-object tests/Data/Docs/OneLevel/Flutter-Self-Service-Index.md
```

## File size

The GitHub response returns `size` and this matches the local one achieved with PHP command

```php
filesize('tests/Data/Docs/OneLevel/Flutter-Self-Service-Index.md');
```
References
- https://stackoverflow.com/a/28881708
