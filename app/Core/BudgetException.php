<?php
/** Kegagalan aturan bisnis anggaran (mis. saldo tidak cukup). Pesannya aman ditampilkan ke pengguna. */
class BudgetException extends RuntimeException {}
