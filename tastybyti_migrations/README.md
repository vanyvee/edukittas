# TastyByti — Laravel Migrations & Seeders

## Files

### Migrations (run in this order)
| File | Table |
|------|-------|
| 000001_create_roles_table | roles |
| 000002_create_users_table | users |
| 000003_create_categories_table | categories |
| 000004_create_products_table | products |
| 000005_create_raw_materials_table | raw_materials |
| 000006_create_restock_logs_table | restock_logs |
| 000007_create_recipes_table | recipes |
| 000008_create_orders_table | orders |
| 000009_create_order_items_table | order_items |
| 000010_create_payments_table | payments |

### Seeders
| File | Seeds |
|------|-------|
| RoleSeeder | 5 roles (ceo, supervisor, cashier, cook, admin) |
| CategorySeeder | 3 categories (FOOD, DRINK, SERVICE) |
| ProductSeeder | All products from TastyByti frontend |
| DatabaseSeeder | Orchestrates all seeders |

---

## Setup

1. Copy migration files → `database/migrations/`
2. Copy seeder files → `database/seeders/`
3. Run:

```bash
php artisan migrate
php artisan db:seed
```

Or fresh install:

```bash
php artisan migrate:fresh --seed
```

---

## Key Design Decisions

**`order_items` snapshots name + price**
When a product price changes later, all historical orders remain accurate.
Never join order_items to products for display — use the snapshot columns.

**`products.price` is nullable**
DELIVERY and PAINT SIP FOOD have no fixed price. `is_price_open = true`
signals the frontend to show a price input field at order time.

**`orders.status` enum**
`pending → cooking → ready → completed → cancelled`
Not used by the POS yet, but required for the cook/staff dashboard later.
Adding it now avoids a painful migration later.

**`payments` is a separate table**
Currently one payment per order. When Opay or split-payment comes in,
you add rows here without touching the orders table.

**`restock_logs` records before/after stock**
Full audit trail — you always know who added how much and what the stock
was before and after. The `user_id` is nullable for system/import events.

---

## Next Steps
- [ ] Create `AdminUserSeeder` to seed your first CEO account
- [ ] Create `RawMaterialSeeder` from your existing materials list
- [ ] Add Laravel Sanctum for API auth
- [ ] Set up Laravel Reverb for real-time order events
