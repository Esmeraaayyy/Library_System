import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:library_frontend/main.dart';

void main() {
  testWidgets('Library dashboard loads successfully',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: DashboardPage(
          user: const {
            'name': 'Test Librarian',
            'role': 'librarian',
          },
          token: 'test-token',
          onLogout: () {},
        ),
      ),
    );

    expect(find.text('Library Management System'), findsOneWidget);
    expect(find.text('Dashboard'), findsOneWidget);
    expect(find.text('Welcome, Test Librarian!'), findsOneWidget);
    expect(find.text('Notification Summary'), findsOneWidget);
    expect(find.text('View Notification Summary'), findsOneWidget);
  });
}