
import 'package:flutter/material.dart';
import 'screens/login_page.dart';
import 'services/api_service.dart';

void main() {
  runApp(const LibraryApp());
}

class LibraryApp extends StatefulWidget {
  const LibraryApp({super.key});

  @override
  State<LibraryApp> createState() => _LibraryAppState();
}

class _LibraryAppState extends State<LibraryApp> {
  Map<String, dynamic>? _user;
  String? _token;

  void _handleLogin(Map<String, dynamic> user, String token) {
    setState(() {
      _user = user;
      _token = token;
    });
  }

  Future<void> _logout() async {
    final token = _token;

    try {
      if (token != null) {
        await ApiService.logout(token);
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Server logout failed. Your local session will still end.',
            ),
          ),
        );
      }
    }

    if (!mounted) return;

    setState(() {
      _user = null;
      _token = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Library Overdue Notification System',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF263A63),
        ),
        useMaterial3: true,
      ),
      home: _user == null
          ? LoginPage(onLoginSuccess: _handleLogin)
          : DashboardPage(
              user: _user!,
              token: _token!,
              onLogout: _logout,
            ),
    );
  }
}

class DashboardPage extends StatelessWidget {
  final Map<String, dynamic> user;
  final String token;
  final VoidCallback onLogout;

  const DashboardPage({
    super.key,
    required this.user,
    required this.token,
    required this.onLogout,
  });

  @override
  Widget build(BuildContext context) {
    final role = user['role']?.toString() ?? 'borrower';

    final canViewNotifications =
        role == 'librarian' || role == 'supervisor';

    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        backgroundColor: const Color(0xFF263A63),
        foregroundColor: Colors.white,
        title: const Text('Library Management System'),
        actions: [
          IconButton(
            tooltip: 'Logout',
            onPressed: onLogout,
            icon: const Icon(Icons.logout),
          ),
        ],
      ),

      drawer: Drawer(
        child: ListView(
          padding: EdgeInsets.zero,
          children: [
            DrawerHeader(
              decoration: const BoxDecoration(
                color: Color(0xFF263A63),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  const Icon(
                    Icons.local_library,
                    color: Colors.white,
                    size: 40,
                  ),
                  const SizedBox(height: 10),
                  const Text(
                    'Library System',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    user['name']?.toString() ?? '',
                    style: const TextStyle(color: Colors.white),
                  ),
                  Text(
                    role.toUpperCase(),
                    style: const TextStyle(color: Colors.white70),
                  ),
                ],
              ),
            ),

            const ListTile(
              leading: Icon(Icons.dashboard),
              title: Text('Dashboard'),
            ),

            if (canViewNotifications)
              ListTile(
                leading: const Icon(Icons.notifications),
                title: const Text('Notifications'),
                onTap: () {
                  Navigator.pop(context);

                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => NotificationSummaryPage(
                        token: token,
                      ),
                    ),
                  );
                },
              ),

            ListTile(
              leading: const Icon(Icons.menu_book),
              title: const Text('Books'),
              onTap: () {
                Navigator.pop(context);

                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => BooksPage(token: token),
                  ),
                );
              },
            ),

            const ListTile(
              leading: Icon(Icons.bookmark),
              title: Text('Borrowings'),
            ),
          ],
        ),
      ),

      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Dashboard',
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Text('Welcome, ${user['name'] ?? 'User'}!'),
            const SizedBox(height: 8),
            Text('Account role: ${role.toUpperCase()}'),
            const SizedBox(height: 24),

            if (canViewNotifications)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Notification Summary',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 12),
                      const Text(
                        'You are authorized to access notification monitoring.',
                      ),
                      const SizedBox(height: 12),
                      ElevatedButton.icon(
                        onPressed: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => NotificationSummaryPage(
                                token: token,
                              ),
                            ),
                          );
                        },
                        icon: const Icon(Icons.notifications),
                        label: const Text('View Notification Summary'),
                      ),
                    ],
                  ),
                ),
              )
            else
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(20),
                  child: Text(
                    'Borrower account: You can access your borrowing '
                    'information here. Notification administration '
                    'is restricted to authorized staff.',
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

// BOOKS PAGE
class BooksPage extends StatefulWidget {
  final String token;

  const BooksPage({
    super.key,
    required this.token,
  });

  @override
  State<BooksPage> createState() => _BooksPageState();
}

class _BooksPageState extends State<BooksPage> {
  late Future<List<dynamic>> _booksFuture;

  @override
  void initState() {
    super.initState();
    _loadBooks();
  }

  void _loadBooks() {
    _booksFuture = ApiService.getBooks(widget.token);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        title: const Text('Books'),
        backgroundColor: const Color(0xFF263A63),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            tooltip: 'Refresh Books',
            onPressed: () {
              setState(_loadBooks);
            },
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),

      body: FutureBuilder<List<dynamic>>(
        future: _booksFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(),
            );
          }

          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(
                      Icons.error_outline,
                      color: Colors.red,
                      size: 50,
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      'Unable to load books.',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      '${snapshot.error}',
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton.icon(
                      onPressed: () {
                        setState(_loadBooks);
                      },
                      icon: const Icon(Icons.refresh),
                      label: const Text('Try Again'),
                    ),
                  ],
                ),
              ),
            );
          }

          final books = snapshot.data ?? [];

          if (books.isEmpty) {
            return const Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.menu_book,
                    size: 60,
                    color: Colors.grey,
                  ),
                  SizedBox(height: 12),
                  Text(
                    'No books found.',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            );
          }

          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text(
                'Book Records (${books.length})',
                style: const TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 16),

              ...books.map((book) {
                final totalCopies =
                    int.tryParse('${book['total_copies']}') ?? 0;

                final availableCopies =
                    int.tryParse('${book['available_copies']}') ?? 0;

                return Card(
                  margin: const EdgeInsets.only(bottom: 14),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const CircleAvatar(
                              radius: 24,
                              backgroundColor: Color(0xFFE5EAF5),
                              child: Icon(
                                Icons.menu_book,
                                color: Color(0xFF263A63),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment:
                                    CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    book['title']?.toString() ??
                                        'Untitled Book',
                                    style: const TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    'Author: ${book['author'] ?? 'Unknown'}',
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),

                        const Divider(height: 24),

                        Text(
                          'ISBN: ${book['isbn'] ?? 'Not available'}',
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'Category: ${book['category'] ?? 'Uncategorized'}',
                        ),
                        const SizedBox(height: 6),
                        Text('Total Copies: $totalCopies'),
                        const SizedBox(height: 6),

                        Row(
                          children: [
                            const Text('Available Copies: '),
                            Text(
                              '$availableCopies',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: availableCopies > 0
                                    ? Colors.green
                                    : Colors.red,
                              ),
                            ),
                            const Spacer(),
                            Text(
                              availableCopies > 0
                                  ? 'Available'
                                  : 'Unavailable',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: availableCopies > 0
                                    ? Colors.green
                                    : Colors.red,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              }),
            ],
          );
        },
      ),
    );
  }
}

// NOTIFICATION SUMMARY PAGE
class NotificationSummaryPage extends StatefulWidget {
  final String token;

  const NotificationSummaryPage({
    super.key,
    required this.token,
  });

  @override
  State<NotificationSummaryPage> createState() =>
      _NotificationSummaryPageState();
}

class _NotificationSummaryPageState
    extends State<NotificationSummaryPage> {
  late Future<Map<String, dynamic>> _summaryFuture;

  @override
  void initState() {
    super.initState();
    _loadSummary();
  }

  void _loadSummary() {
    _summaryFuture =
        ApiService.getNotificationSummary(widget.token);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notification Summary'),
        backgroundColor: const Color(0xFF263A63),
        foregroundColor: Colors.white,
      ),

      body: FutureBuilder<Map<String, dynamic>>(
        future: _summaryFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(),
            );
          }

          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text(
                  'Unable to load summary: ${snapshot.error}',
                ),
              ),
            );
          }

          final data = snapshot.data!;

          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              SummaryCard(
                title: 'Total Notifications',
                value: '${data['total'] ?? 0}',
                icon: Icons.notifications,
                color: Colors.blue,
              ),
              SummaryCard(
                title: 'Pending',
                value: '${data['pending'] ?? 0}',
                icon: Icons.schedule,
                color: Colors.orange,
              ),
              SummaryCard(
                title: 'Retrying',
                value: '${data['retrying'] ?? 0}',
                icon: Icons.refresh,
                color: Colors.deepOrange,
              ),
              SummaryCard(
                title: 'Sent',
                value: '${data['sent'] ?? 0}',
                icon: Icons.check_circle,
                color: Colors.green,
              ),
              SummaryCard(
                title: 'Failed',
                value: '${data['failed'] ?? 0}',
                icon: Icons.error,
                color: Colors.red,
              ),
              const SizedBox(height: 16),
              OutlinedButton.icon(
                onPressed: () {
                  setState(_loadSummary);
                },
                icon: const Icon(Icons.refresh),
                label: const Text('Refresh Summary'),
              ),
            ],
          );
        },
      ),
    );
  }
}

// REUSABLE SUMMARY CARD
class SummaryCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;

  const SummaryCard({
    super.key,
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: color.withValues(alpha: 0.12),
          child: Icon(icon, color: color),
        ),
        title: Text(title),
        trailing: Text(
          value,
          style: const TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }
}
