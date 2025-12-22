// Root layout - provides minimal HTML structure
// Actual locale-specific layouts are in [locale]/layout.tsx

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html suppressHydrationWarning>
      <body>{children}</body>
    </html>
  );
}
