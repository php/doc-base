
This is a test only branch/PR, with the objective of studying ways to make Github CIs to *not* ignore posterior changes on main/master.

DO NOT MERGE.

The doc-base history before branching:

```
commit c45b8a3d6085b8b18a0938e5ac571403da5541ed master HEAD
commit 982980a58b8b05b5cbdb832aa426a5b29df77a46
commit 9391318e46e6fdd09721a9f5e3553089e4382009 <- branch from here
commit 4555190558990db31583a997bfa010d1823d1cae
commit d9e120747d082ae07c1e924b4f85e8bede7f680a older
```

The branching was intentionally done in the past of master to test a secondary question: future commits on master HEAD changes anything in GH actions/checkout?

Changed README.md, added, committed and then PR opened.
