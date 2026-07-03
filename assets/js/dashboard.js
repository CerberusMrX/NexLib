document.addEventListener('DOMContentLoaded', () => {
    const userStr = localStorage.getItem('user');
    if (!userStr) {
        window.location.href = 'auth.html';
        return;
    }

    const user = JSON.parse(userStr);
    document.getElementById('user-name').innerText = user.name;
    if (user.role === 'admin') {
        document.getElementById('admin-links').style.display = 'block';
        const myBooksTab = document.querySelector('[data-view="my-books"]');
        if (myBooksTab) myBooksTab.style.display = 'none';
    }

    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            navItems.forEach(i => i.classList.remove('active', 'glass'));
            item.classList.add('active', 'glass');
            loadView(item.dataset.view);
        });
    });

    document.getElementById('logout-btn').addEventListener('click', () => {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = 'index.html';
    });

    loadView('overview');
});

async function loadView(view) {
    const title = document.getElementById('view-title');
    const content = document.getElementById('view-content');
    const actions = document.getElementById('header-actions');
    actions.innerHTML = '';

    switch (view) {
        case 'overview':
            title.innerText = 'Dashboard Overview';
            renderOverview();
            break;
        case 'books':
            title.innerText = 'Book Search';
            renderBooks();
            break;
        case 'my-books':
            title.innerText = 'My Borrowed Books';
            renderTransactions(false);
            break;
        case 'profile':
            title.innerText = 'My Profile Settings';
            renderProfile();
            break;
        case 'manage-books':
            title.innerText = 'Manage Library Books';
            actions.innerHTML = '<button class="btn-outline" onclick="exportTableToCSV(\'books-table\', \'books_report.csv\')" style="margin-right: 1rem; border-color: var(--primary); color: white;">Export CSV</button><button class="btn-primary" onclick="openBookModal()">+ Add Book</button>';
            renderBooks(true);
            break;
        case 'manage-users':
            title.innerText = 'User Management';
            actions.innerHTML = '<button class="btn-outline" onclick="exportTableToCSV(\'users-table\', \'users_report.csv\')" style="border-color: var(--primary); color: white;">Export CSV</button>';
            renderUsers();
            break;
        case 'manage-transactions':
            title.innerText = 'All Transactions & Reports';
            actions.innerHTML = '<button class="btn-outline" onclick="exportTableToCSV(\'all-trans-table\', \'transactions_report.csv\')" style="border-color: var(--primary); color: white;">Export CSV</button>';
            renderManageTransactions();
            break;
    }
}

function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    let csv = [];
    for (let row of table.rows) {
        let cols = row.querySelectorAll('td, th');
        let data = [];
        for (let j = 0; j < cols.length; j++) {
            if (table.rows[0].cells[j] && table.rows[0].cells[j].innerText.trim() !== 'Actions') {
                data.push('"' + cols[j].innerText.replace(/"/g, '""').replace(/\n/g, ' ').trim() + '"');
            }
        }
        if (data.length > 0) csv.push(data.join(','));
    }
    const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
    const tempLink = document.createElement('a');
    tempLink.download = filename;
    tempLink.href = window.URL.createObjectURL(csvFile);
    tempLink.style.display = 'none';
    document.body.appendChild(tempLink);
    tempLink.click();
    document.body.removeChild(tempLink);
}

async function renderOverview() {
    const content = document.getElementById('view-content');
    content.innerHTML = '<div class="stats-grid"><div class="stat-card glass"><h3>Total Books</h3><div class="value" id="stat-books">...</div></div><div class="stat-card glass"><h3>Available Now</h3><div class="value" id="stat-available">...</div></div><div class="stat-card glass"><h3>Your Borrowings</h3><div class="value" id="stat-user-trans">...</div></div></div>';

    try {
        const books = await API.get('books');
        const transactions = await API.get('transactions');
        const user = JSON.parse(localStorage.getItem('user'));

        document.getElementById('stat-books').innerText = books.length;
        document.getElementById('stat-available').innerText = books.reduce((acc, b) => acc + parseInt(b.available), 0);
        document.getElementById('stat-user-trans').innerText = transactions.filter(t => t.status === 'borrowed' && t.user_id == user.id).length;
    } catch (e) {
        console.error(e);
    }
}

async function renderBooks(isAdmin = false) {
    const content = document.getElementById('view-content');
    content.innerHTML = '<div class="glass" style="padding: 1.5rem;"><div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;"><input type="text" id="search-input" placeholder="Search by title, author, or ISBN..."><button class="btn-primary" onclick="searchBooks()">Search</button></div><table id="books-table"><thead><tr><th style="width: 80px;">Cover</th><th>Title</th><th>Author</th><th>ISBN</th><th>Status</th><th>Actions</th></tr></thead><tbody id="books-body"></tbody></table></div>';

    searchBooks(isAdmin);
}

async function searchBooks(isAdmin = false) {
    const query = document.getElementById('search-input')?.value || '';
    const tbody = document.getElementById('books-body');
    tbody.innerHTML = '<tr><td colspan="6">Loading...</td></tr>';

    try {
        const books = await API.get('books?_=' + new Date().getTime()); // Search logic should ideally be on backend, but let's filter here for simple demo
        const filtered = books.filter(b =>
            b.title.toLowerCase().includes(query.toLowerCase()) ||
            b.author.toLowerCase().includes(query.toLowerCase()) ||
            b.isbn.toLowerCase().includes(query.toLowerCase())
        );

        tbody.innerHTML = '';
        filtered.forEach(book => {
            const tr = document.createElement('tr');
            const statusClass = book.available > 0 ? 'badge-success' : 'badge-danger';
            const statusText = book.available > 0 ? 'Available' : 'Borrowed';

            const currentUser = JSON.parse(localStorage.getItem('user'));
            let btnAction = '';
            
            if (isAdmin) {
                btnAction = `<button onclick="openBookModal(${JSON.stringify(book).replace(/"/g, '&quot;')})" style="color: var(--primary); background: none; font-size: 0.8rem; margin-right: 0.5rem;">Edit</button>`;
            } else if (currentUser.role === 'admin') {
                btnAction = '<span style="color: var(--text-secondary); font-size: 0.85rem;">View Only</span>';
            } else {
                btnAction = (book.available > 0 ? `<button onclick="borrowBook(${book.id})" class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.8rem;">Borrow</button>` : 'N/A');
            }

            const coverCell = book.image_url ? 
                `<img src="${book.image_url}" alt="Cover" style="width: 70px; height: 100px; object-fit: cover; border-radius: 6px; display: block; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">` : 
                `<div style="width: 70px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 6px; display:flex; align-items:center; justify-content:center; font-size: 11px; color: rgba(255,255,255,0.4); text-align:center; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">No<br>Cover</div>`;

            tr.innerHTML = `
                <td style="padding: 0.5rem;">${coverCell}</td>
                <td><div style="font-weight: 600;">${book.title}</div></td>
                <td>${book.author}</td>
                <td>${book.isbn}</td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td>${btnAction}</td>
            `;
            tbody.appendChild(tr);
        });
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="6">Error: ${e.message}</td></tr>`;
    }
}

async function borrowBook(bookId) {
    if (!confirm('Are you sure you want to borrow this book?')) return;
    try {
        await API.post('borrow', { book_id: bookId });
        alert('Book borrowed successfully!');
        searchBooks();
    } catch (e) {
        alert(e.message);
    }
}

async function renderTransactions(isAdmin = false) {
    const content = document.getElementById('view-content');
    content.innerHTML = '<div class="glass" style="padding: 1.5rem;"><table><thead><tr><th>Book</th><th>Issue Date</th><th>Due Date</th><th>Fine</th><th>Status</th><th>Actions</th></tr></thead><tbody id="trans-body"></tbody></table></div>';

    const tbody = document.getElementById('trans-body');
    try {
        const trans = await API.get('transactions');
        tbody.innerHTML = '';
        trans.forEach(t => {
            const tr = document.createElement('tr');
            const isOverdue = new Date(t.due_date) < new Date() && t.status === 'borrowed';
            const statusClass = t.status === 'returned' ? 'badge-success' : (isOverdue ? 'badge-danger' : 'badge-warning');
            const statusText = isOverdue ? 'Overdue' : t.status.charAt(0).toUpperCase() + t.status.slice(1);

            const actionBtn = t.status === 'borrowed' ?
                `<button onclick="returnBook(${t.id})" class="btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">Return</button>` : '-';

            tr.innerHTML = `
                <td>${t.book_title}</td>
                <td>${t.issue_date}</td>
                <td>${t.due_date}</td>
                <td>Rs. ${t.fine}</td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td>${actionBtn}</td>
            `;
            tbody.appendChild(tr);
        });
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="6">Error: ${e.message}</td></tr>`;
    }
}

async function returnBook(transId) {
    if (!confirm('Are you sure you want to return this book?')) return;
    try {
        await API.post('return', { transaction_id: transId });
        alert('Book returned successfully!');
        renderTransactions();
    } catch (e) {
        alert(e.message);
    }
}

async function renderManageTransactions() {
    const content = document.getElementById('view-content');
    content.innerHTML = '<div class="glass" style="padding: 1.5rem; overflow-x: auto;"><table id="all-trans-table"><thead><tr><th>User Name</th><th>Book Title</th><th>Issue Date</th><th>Due Date</th><th>Fine</th><th>Status</th></tr></thead><tbody id="all-trans-body"></tbody></table></div>';

    const tbody = document.getElementById('all-trans-body');
    try {
        const trans = await API.get('transactions');
        tbody.innerHTML = '';
        trans.forEach(t => {
            const tr = document.createElement('tr');
            const isOverdue = new Date(t.due_date) < new Date() && t.status === 'borrowed';
            const statusClass = t.status === 'returned' ? 'badge-success' : (isOverdue ? 'badge-danger' : 'badge-warning');
            const statusText = isOverdue ? 'Overdue' : t.status.charAt(0).toUpperCase() + t.status.slice(1);

            const fineAmount = parseFloat(t.fine) || 0;
            const fineColor = fineAmount > 0 ? 'var(--danger)' : 'inherit';

            tr.innerHTML = `
                <td><div style="font-weight: 600;">${t.user_name}</div></td>
                <td>${t.book_title}</td>
                <td>${t.issue_date}</td>
                <td>${t.due_date}</td>
                <td><span style="color: ${fineColor}; font-weight: 600;">Rs. ${t.fine}</span></td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
            `;
            tbody.appendChild(tr);
        });
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="6">Error: ${e.message}</td></tr>`;
    }
}

async function renderUsers() {
    const content = document.getElementById('view-content');
    content.innerHTML = '<div class="glass" style="padding: 1.5rem; overflow-x: auto;"><table id="users-table"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th style="text-align: right;">Actions</th></tr></thead><tbody id="users-body"></tbody></table></div>';

    const tbody = document.getElementById('users-body');
    const currentUser = JSON.parse(localStorage.getItem('user'));
    try {
        const users = await API.get('users');
        tbody.innerHTML = '';
        users.forEach(u => {
            const tr = document.createElement('tr');
            const roleBadge = u.role === 'admin' ? 'badge-warning' : 'badge-success';
            
            let actionBtn = '';
            if (u.id !== currentUser.id) {
                actionBtn = `<button onclick="deleteUser(${u.id}, '${u.name.replace(/'/g, "\\'")}')" class="btn-outline" style="border-color: var(--danger); color: var(--danger); padding: 0.4rem 0.8rem; font-size: 0.75rem; float: right;">Remove</button>`;
            } else {
                actionBtn = '<span style="color: var(--text-secondary); font-size: 0.8rem; float: right;">Current</span>';
            }

            tr.innerHTML = `<td><div style="font-weight: 600;">${u.name}</div></td><td style="color: var(--text-secondary);">${u.email}</td><td><span class="badge ${roleBadge}">${u.role.toUpperCase()}</span></td><td>${u.created_at.split(' ')[0]}</td><td>${actionBtn}</td>`;
            tbody.appendChild(tr);
        });
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5">Error: ${e.message}</td></tr>`;
    }
}

async function renderProfile() {
    const content = document.getElementById('view-content');
    const user = JSON.parse(localStorage.getItem('user'));
    
    content.innerHTML = `
        <div class="glass" style="padding: 2.5rem; max-width: 600px; margin: 0 auto; border-radius: 1.5rem;">
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">Update your personal details. Leave the password field blank if you do not wish to change it.</p>
            <form id="profile-form">
                <div class="form-group">
                    <label style="color: var(--text-primary);">Full Name</label>
                    <input type="text" id="profile-name" value="${user.name.replace(/"/g, '&quot;')}" required style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                </div>
                <div class="form-group">
                    <label style="color: var(--text-primary);">Email Address</label>
                    <input type="email" id="profile-email" value="${user.email.replace(/"/g, '&quot;')}" required style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                </div>
                <div class="form-group">
                    <label style="color: var(--text-primary);">Current Password (Required if changing password)</label>
                    <input type="password" id="profile-old-password" placeholder="Leave blank if not changing your password" style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                </div>
                <div class="form-group">
                    <label style="color: var(--text-primary);">New Password</label>
                    <input type="password" id="profile-password" placeholder="Leave blank to keep current password" style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; padding: 1rem; margin-top: 1.5rem; font-size: 1rem;">Update Profile Settings</button>
            </form>
        </div>
    `;

    document.getElementById('profile-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const newPassword = document.getElementById('profile-password').value;
        const oldPassword = document.getElementById('profile-old-password').value;

        if (newPassword && !oldPassword) {
            alert('Please enter your CURRENT password to authorize setting a new password.');
            return;
        }

        const btn = e.target.querySelector('button');
        const origText = btn.innerText;
        btn.innerText = 'Updating...';
        btn.disabled = true;

        const data = {
            name: document.getElementById('profile-name').value,
            email: document.getElementById('profile-email').value,
            password: newPassword,
            old_password: oldPassword
        };

        try {
            await API.put('users', data);
            alert('Profile successfully updated!');
            
            const updatedUser = { ...user, name: data.name, email: data.email };
            localStorage.setItem('user', JSON.stringify(updatedUser));
            document.getElementById('user-name').innerText = data.name;
            
            document.getElementById('profile-old-password').value = '';
            document.getElementById('profile-password').value = '';
        } catch (err) {
            alert(err.message);
        }
        
        btn.innerText = origText;
        btn.disabled = false;
    });
}

async function deleteUser(userId, userName) {
    if (!confirm(`Are you absolutely sure you want to remove user "${userName}"? This action cannot be undone.`)) return;
    try {
        await API.delete('users', userId);
        renderUsers();
    } catch (e) {
        alert(e.message);
    }
}

// Book Modal Logic
function openBookModal(book = null) {
    const modal = document.getElementById('book-modal');
    const form = document.getElementById('book-form');
    const title = document.getElementById('modal-title');

    if (book) {
        title.innerText = 'Edit Book';
        document.getElementById('book-id').value = book.id;
        document.getElementById('book-title-input').value = book.title;
        document.getElementById('book-author').value = book.author;
        document.getElementById('book-isbn').value = book.isbn;
        document.getElementById('book-image-url').value = book.image_url || '';
        document.getElementById('book-image-file').value = '';
        document.getElementById('book-quantity').value = book.quantity;
    } else {
        title.innerText = 'Add New Book';
        form.reset();
        document.getElementById('book-id').value = '';
        document.getElementById('book-image-file').value = '';
    }
    modal.classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

document.getElementById('book-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('book-id').value;
    
    const fileInput = document.getElementById('book-image-file');
    let finalImageUrl = document.getElementById('book-image-url').value;

    const submitBtn = document.querySelector('#book-form button[type="submit"]');
    const origText = submitBtn.innerText;

    if (fileInput && fileInput.files.length > 0) {
        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        submitBtn.innerText = 'Uploading...';
        submitBtn.disabled = true;

        try {
            const uploadRes = await fetch('api/upload.php', { method: 'POST', body: formData });
            const uploadData = await uploadRes.json();
            if (uploadData.url) {
                finalImageUrl = uploadData.url;
            } else {
                alert(uploadData.message || 'Upload failed');
                submitBtn.innerText = origText;
                submitBtn.disabled = false;
                return;
            }
        } catch (err) {
            alert('Upload error: ' + err.message);
            submitBtn.innerText = origText;
            submitBtn.disabled = false;
            return;
        }

        submitBtn.innerText = origText;
        submitBtn.disabled = false;
    }

    const data = {
        title: document.getElementById('book-title-input').value,
        author: document.getElementById('book-author').value,
        isbn: document.getElementById('book-isbn').value,
        image_url: finalImageUrl,
        quantity: document.getElementById('book-quantity').value
    };

    try {
        if (id) {
            data.id = id;
            await API.put('books', data);
        } else {
            await API.post('books', data);
        }
        closeModal('book-modal');
        searchBooks(true);
    } catch (e) {
        alert(e.message);
    }
});
