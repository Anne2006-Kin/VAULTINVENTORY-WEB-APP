const express = require('express');
const cors = require('cors');
const bcrypt = require('bcryptjs');
const mysql = require('mysql2');
const path = require('path');
const jwt = require('jsonwebtoken');

const app = express();
app.use(cors());
app.use(express.json());
app.use(express.static(__dirname));

// Database connection
const pool = mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'warehouse_inventory',
    waitForConnections: true,
    connectionLimit: 10
});

const promisePool = pool.promise();
const JWT_SECRET = 'your_super_secret_key_change_this';

// Log activity function
async function logActivity(userId, userName, actionType, itemType, itemName, details = null) {
    try {
        await promisePool.query(
            'INSERT INTO user_activity (user_id, user_name, action_type, item_type, item_name, details) VALUES (?, ?, ?, ?, ?, ?)',
            [userId, userName, actionType, itemType, itemName, details || '']
        );
        console.log(`✅ Activity logged: ${actionType} - ${itemName} by ${userName}`);
    } catch (err) {
        console.error('Log activity error:', err);
    }
}

// Auth middleware
function authenticateToken(req, res, next) {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];
    
    if (!token) {
        return res.status(401).json({ success: false, message: 'Access token required' });
    }
    
    try {
        const decoded = jwt.verify(token, JWT_SECRET);
        req.user = decoded;
        next();
    } catch (err) {
        return res.status(403).json({ success: false, message: 'Invalid token' });
    }
}

// Test database connection
async function testDb() {
    try {
        const conn = await promisePool.getConnection();
        console.log('✅ MySQL Connected!');
        conn.release();
        return true;
    } catch (err) {
        console.error('❌ MySQL Error:', err.message);
        return false;
    }
}

// ============ AUTH ROUTES ============
app.post('/api/auth/login', async (req, res) => {
    const { username_or_email, password } = req.body;
    try {
        const [users] = await promisePool.query(
            'SELECT * FROM users WHERE username = ? OR email = ?',
            [username_or_email, username_or_email]
        );
        if (users.length === 0) {
            return res.status(401).json({ success: false, message: 'Invalid credentials' });
        }
        const valid = await bcrypt.compare(password, users[0].password_hash);
        if (!valid) {
            return res.status(401).json({ success: false, message: 'Invalid credentials' });
        }
        
        const token = jwt.sign(
            { userId: users[0].user_id, username: users[0].username, full_name: users[0].full_name, role: users[0].role },
            JWT_SECRET,
            { expiresIn: '7d' }
        );
        
        await logActivity(users[0].user_id, users[0].full_name || users[0].username, 'LOGIN', 'user', users[0].username, 'User logged in successfully');
        
        res.json({ 
            success: true, 
            token: token,
            user: { 
                user_id: users[0].user_id,
                username: users[0].username, 
                full_name: users[0].full_name, 
                role: users[0].role 
            }
        });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

app.post('/api/auth/register', async (req, res) => {
    const { username, email, password, full_name } = req.body;
    try {
        const hashedPassword = await bcrypt.hash(password, 10);
        const [result] = await promisePool.query(
            'INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)',
            [username, email, hashedPassword, full_name || username, 'Staff']
        );
        
        const token = jwt.sign(
            { userId: result.insertId, username: username, full_name: full_name || username, role: 'Staff' },
            JWT_SECRET,
            { expiresIn: '7d' }
        );
        
        res.json({ 
            success: true, 
            message: 'Registration successful',
            token: token,
            user: { user_id: result.insertId, username: username, full_name: full_name || username, role: 'Staff' }
        });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

app.get('/api/auth/profile', authenticateToken, async (req, res) => {
    try {
        const [users] = await promisePool.query(
            'SELECT user_id, username, email, full_name, role, created_at FROM users WHERE user_id = ?',
            [req.user.userId]
        );
        if (users.length > 0) {
            res.json({ success: true, user: users[0] });
        } else {
            res.status(404).json({ success: false, message: 'User not found' });
        }
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

app.put('/api/auth/profile', authenticateToken, async (req, res) => {
    const { full_name, email, password } = req.body;
    try {
        let query = 'UPDATE users SET full_name = ?, email = ?';
        let params = [full_name, email];
        if (password && password.length >= 6) {
            const hashedPassword = await bcrypt.hash(password, 10);
            query += ', password_hash = ?';
            params.push(hashedPassword);
        }
        query += ' WHERE user_id = ?';
        params.push(req.user.userId);
        await promisePool.query(query, params);
        
        await logActivity(req.user.userId, req.user.full_name || req.user.username, 'UPDATE', 'profile', 'Account', 'Profile updated');
        
        res.json({ success: true, message: 'Profile updated' });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// ============ USER ROUTES ============
app.get('/api/users', authenticateToken, async (req, res) => {
    try {
        const [users] = await promisePool.query(
            'SELECT user_id, username, email, full_name, role, is_active, created_at FROM users ORDER BY created_at DESC'
        );
        res.json({ success: true, users });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// ============ PRODUCT ROUTES ============
app.get('/api/products', authenticateToken, async (req, res) => {
    try {
        const [rows] = await promisePool.query(`
            SELECT p.*, s.supplier_name 
            FROM products p
            LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
            ORDER BY p.created_at DESC
        `);
        res.json({ success: true, products: rows });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

app.post('/api/products', authenticateToken, async (req, res) => {
    const { name, sku, category, quantity, unit_price, reorder_level, location, supplier_id, handled_by } = req.body;
    try {
        let handledByName = null;
        if (handled_by) {
            const [user] = await promisePool.query('SELECT full_name, username FROM users WHERE user_id = ?', [handled_by]);
            if (user.length > 0) {
                handledByName = user[0].full_name || user[0].username;
            }
        }
        
        const [result] = await promisePool.query(
            `INSERT INTO products (product_name, sku, category, quantity, unit_price, reorder_level, location, supplier_id, handled_by, handled_by_name, created_by, created_by_name) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            [name, sku, category || null, quantity || 0, unit_price || 0, reorder_level || 10, location || null, supplier_id || null, handled_by || null, handledByName, req.user.userId, req.user.full_name || req.user.username]
        );
        
        await logActivity(req.user.userId, req.user.full_name || req.user.username, 'ADD', 'product', name, `Added product with SKU: ${sku}`);
        
        res.json({ success: true, message: 'Product added!', id: result.insertId });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

app.put('/api/products/:id', authenticateToken, async (req, res) => {
    const { name, sku, category, quantity, unit_price, reorder_level, location, supplier_id, handled_by } = req.body;
    try {
        await promisePool.query(
            `UPDATE products SET product_name=?, sku=?, category=?, quantity=?, unit_price=?, reorder_level=?, location=?, supplier_id=?, handled_by=? WHERE product_id=?`,
            [name, sku, category, quantity, unit_price, reorder_level, location, supplier_id, handled_by, req.params.id]
        );
        
        await logActivity(req.user.userId, req.user.full_name || req.user.username, 'UPDATE', 'product', name, `Updated product SKU: ${sku}`);
        
        res.json({ success: true, message: 'Product updated' });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

app.delete('/api/products/:id', authenticateToken, async (req, res) => {
    try {
        const [product] = await promisePool.query('SELECT product_name, sku FROM products WHERE product_id = ?', [req.params.id]);
        const productName = product.length > 0 ? product[0].product_name : 'Unknown';
        
        await promisePool.query('DELETE FROM products WHERE product_id = ?', [req.params.id]);
        
        await logActivity(req.user.userId, req.user.full_name || req.user.username, 'DELETE', 'product', productName, `Deleted product: ${productName}`);
        
        res.json({ success: true, message: 'Product deleted' });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

app.post('/api/products/:id/stock-movement', authenticateToken, async (req, res) => {
    const { quantity, movement_type, reason, handled_by } = req.body;
    try {
        const [product] = await promisePool.query('SELECT product_name FROM products WHERE product_id = ?', [req.params.id]);
        if (!product.length) return res.status(404).json({ success: false, error: 'Product not found' });
        
        const [current] = await promisePool.query('SELECT quantity FROM products WHERE product_id = ?', [req.params.id]);
        let newQuantity = current[0].quantity;
        if (movement_type === 'OUT') {
            if (current[0].quantity < quantity) {
                return res.status(400).json({ success: false, error: 'Insufficient stock' });
            }
            newQuantity -= quantity;
        } else {
            newQuantity += quantity;
        }
        
        await promisePool.query('UPDATE products SET quantity = ? WHERE product_id = ?', [newQuantity, req.params.id]);
        
        let handledByName = null;
        if (handled_by) {
            const [user] = await promisePool.query('SELECT full_name, username FROM users WHERE user_id = ?', [handled_by]);
            if (user.length > 0) {
                handledByName = user[0].full_name || user[0].username;
            }
        }
        
        await promisePool.query(
            'INSERT INTO stock_movements (product_id, movement_type, quantity, reason, handled_by, handled_by_name) VALUES (?, ?, ?, ?, ?, ?)',
            [req.params.id, movement_type, quantity, reason || null, handled_by || null, handledByName]
        );
        
        const actionText = movement_type === 'OUT' ? `Sold ${quantity} units of` : `Received ${quantity} units of`;
        await logActivity(req.user.userId, req.user.full_name || req.user.username, movement_type === 'OUT' ? 'SELL' : 'RECEIVE', 'stock', product[0].product_name, `${actionText} ${product[0].product_name}. Reason: ${reason || 'N/A'}`);
        
        res.json({ success: true, message: `Stock ${movement_type === 'OUT' ? 'removed' : 'added'} successfully` });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// ============ SUPPLIER ROUTES ============
app.get('/api/suppliers', authenticateToken, async (req, res) => {
    try {
        const [rows] = await promisePool.query(`
            SELECT s.*, COUNT(p.product_id) as product_count 
            FROM suppliers s
            LEFT JOIN products p ON s.supplier_id = p.supplier_id
            GROUP BY s.supplier_id
            ORDER BY s.supplier_name
        `);
        res.json({ success: true, suppliers: rows });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

app.post('/api/suppliers', authenticateToken, async (req, res) => {
    const { supplier_name, contact_person, email, phone, category, location, handled_by } = req.body;
    try {
        let handledByName = null;
        if (handled_by) {
            const [user] = await promisePool.query('SELECT full_name, username FROM users WHERE user_id = ?', [handled_by]);
            if (user.length > 0) {
                handledByName = user[0].full_name || user[0].username;
            }
        }
        
        const [result] = await promisePool.query(
            `INSERT INTO suppliers (supplier_name, contact_person, email, phone, category, location, handled_by, handled_by_name, created_by, created_by_name) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            [supplier_name, contact_person || null, email || null, phone || null, category || null, location || null, handled_by || null, handledByName, req.user.userId, req.user.full_name || req.user.username]
        );
        
        await logActivity(req.user.userId, req.user.full_name || req.user.username, 'ADD', 'supplier', supplier_name, `Added supplier: ${supplier_name}`);
        
        res.json({ success: true, message: 'Supplier added!', id: result.insertId });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

app.put('/api/suppliers/:id', authenticateToken, async (req, res) => {
    const { supplier_name, contact_person, email, phone, category, location, handled_by } = req.body;
    try {
        await promisePool.query(
            `UPDATE suppliers SET supplier_name=?, contact_person=?, email=?, phone=?, category=?, location=?, handled_by=? WHERE supplier_id=?`,
            [supplier_name, contact_person, email, phone, category, location, handled_by, req.params.id]
        );
        
        await logActivity(req.user.userId, req.user.full_name || req.user.username, 'UPDATE', 'supplier', supplier_name, `Updated supplier: ${supplier_name}`);
        
        res.json({ success: true, message: 'Supplier updated' });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

app.delete('/api/suppliers/:id', authenticateToken, async (req, res) => {
    try {
        const [supplier] = await promisePool.query('SELECT supplier_name FROM suppliers WHERE supplier_id = ?', [req.params.id]);
        const supplierName = supplier.length > 0 ? supplier[0].supplier_name : 'Unknown';
        
        const [products] = await promisePool.query('SELECT COUNT(*) as count FROM products WHERE supplier_id = ?', [req.params.id]);
        if (products[0].count > 0) {
            return res.status(400).json({ success: false, error: 'Cannot delete supplier with existing products' });
        }
        await promisePool.query('DELETE FROM suppliers WHERE supplier_id = ?', [req.params.id]);
        
        await logActivity(req.user.userId, req.user.full_name || req.user.username, 'DELETE', 'supplier', supplierName, `Deleted supplier: ${supplierName}`);
        
        res.json({ success: true, message: 'Supplier deleted' });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// ============ DASHBOARD STATS ============
app.get('/api/dashboard/stats', authenticateToken, async (req, res) => {
    try {
        const [totalProducts] = await promisePool.query('SELECT COUNT(*) as count FROM products');
        const [lowStock] = await promisePool.query('SELECT COUNT(*) as count FROM products WHERE quantity <= reorder_level AND quantity > 0');
        const [criticalStock] = await promisePool.query('SELECT COUNT(*) as count FROM products WHERE quantity = 0');
        const [totalSuppliers] = await promisePool.query('SELECT COUNT(*) as count FROM suppliers');
        const [totalValue] = await promisePool.query('SELECT SUM(quantity * unit_price) as value FROM products');
        
        const [recentActivities] = await promisePool.query(`
            SELECT action_type as type, item_name as name, details as action, user_name as handled_by, created_at as date 
            FROM user_activity 
            ORDER BY created_at DESC 
            LIMIT 20
        `);
        
        console.log(`📊 Dashboard stats: ${totalProducts[0].count} products, ${recentActivities.length} recent activities`);
        
        res.json({
            success: true,
            data: {
                metrics: {
                    totalProducts: totalProducts[0].count || 0,
                    lowStockItems: lowStock[0].count || 0,
                    criticalStock: criticalStock[0].count || 0,
                    totalSuppliers: totalSuppliers[0].count || 0,
                    totalValue: totalValue[0].value || 0
                },
                recentActivities: recentActivities
            }
        });
    } catch (err) {
        console.error('Dashboard stats error:', err);
        res.status(500).json({ success: false, error: err.message });
    }
});

// Serve HTML files
app.get('/', (req, res) => { res.sendFile(path.join(__dirname, 'dashboard.html')); });
app.get('/dashboard.html', (req, res) => { res.sendFile(path.join(__dirname, 'dashboard.html')); });
app.get('/login.html', (req, res) => { res.sendFile(path.join(__dirname, 'login.html')); });
app.get('/register.html', (req, res) => { res.sendFile(path.join(__dirname, 'register.html')); });

// Start server
const PORT = 8080;
app.listen(PORT, async () => {
    console.log(`\n🚀 Server running on http://localhost:${PORT}`);
    console.log(`📋 Open in browser: http://localhost:${PORT}`);
    console.log(`\n📝 Default login: admin / admin123 (if you have an admin user)`);
    await testDb();
});