# TODO

# Releasing new versions
1. Have your work in a separate branch
2. Check that branch out on the production server (unless it's something destructive or that does stuff to the database) just to check that everything works allright. Surf around.
3. Create a PR to master
4. Merge the PR to master
5. Create a new release, use YYYYMMDD-N as tag name
6. On the production server:
```
git pull
gulp
php vendor/bin/phinx migrate
```
