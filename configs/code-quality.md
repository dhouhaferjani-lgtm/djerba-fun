# Code Quality Configuration

> Add these files to enforce consistent code quality across the project.

---

## 1. Root Package.json (Updated)

```json
{
  "name": "go-adventure",
  "version": "0.1.0",
  "private": true,
  "packageManager": "pnpm@9.0.0",
  "scripts": {
    "dev": "pnpm --parallel -r run dev",
    "build": "pnpm -r run build",
    "lint": "pnpm -r run lint",
    "lint:fix": "pnpm -r run lint:fix",
    "typecheck": "pnpm -r run typecheck",
    "test": "pnpm -r run test",
    "format": "prettier --write .",
    "format:check": "prettier --check .",
    "prepare": "husky"
  },
  "devDependencies": {
    "@commitlint/cli": "^19.3.0",
    "@commitlint/config-conventional": "^19.2.2",
    "husky": "^9.0.11",
    "lint-staged": "^15.2.5",
    "prettier": "^3.2.5",
    "typescript": "^5.5.0"
  },
  "lint-staged": {
    "*.{js,jsx,ts,tsx}": ["eslint --fix", "prettier --write"],
    "*.php": ["php ./apps/laravel-api/vendor/bin/pint"],
    "*.{json,md,yml,yaml}": ["prettier --write"]
  }
}
```

---

## 2. Husky Setup

```bash
# Initialize Husky (run after pnpm install)
pnpm exec husky init
```

### .husky/pre-commit

```bash
#!/bin/sh
pnpm exec lint-staged
```

### .husky/pre-push

```bash
#!/bin/sh
pnpm typecheck
pnpm --filter @djerba-fun/schemas build
```

### .husky/commit-msg

```bash
#!/bin/sh
pnpm exec commitlint --edit $1
```

---

## 3. Commitlint Config

### commitlint.config.js

```javascript
module.exports = {
  extends: ['@commitlint/config-conventional'],
  rules: {
    'type-enum': [
      2,
      'always',
      [
        'feat', // New feature
        'fix', // Bug fix
        'docs', // Documentation
        'style', // Formatting, no code change
        'refactor', // Code restructuring
        'perf', // Performance improvement
        'test', // Adding tests
        'chore', // Maintenance
        'ci', // CI/CD changes
        'build', // Build system
        'revert', // Revert commit
      ],
    ],
    'scope-enum': [
      2,
      'always',
      [
        'api', // Laravel backend
        'web', // Next.js frontend
        'ui', // Design system
        'schemas', // Shared schemas
        'sdk', // API SDK
        'docker', // Docker/infra
        'deps', // Dependencies
        'release', // Release process
      ],
    ],
    'subject-case': [2, 'always', 'lower-case'],
    'header-max-length': [2, 'always', 72],
  },
};
```

**Commit examples:**

```
feat(api): add booking cancellation endpoint
fix(web): resolve map marker z-index issue
docs(schemas): add JSDoc comments to booking types
chore(deps): update Laravel to 12.1
```

---

## 4. Prettier Config

### .prettierrc

```json
{
  "semi": true,
  "singleQuote": true,
  "tabWidth": 2,
  "trailingComma": "es5",
  "printWidth": 100,
  "bracketSpacing": true,
  "arrowParens": "always",
  "endOfLine": "lf",
  "plugins": ["prettier-plugin-tailwindcss"],
  "overrides": [
    {
      "files": "*.md",
      "options": {
        "proseWrap": "always"
      }
    }
  ]
}
```

### .prettierignore

```
# Dependencies
node_modules/
vendor/

# Build
dist/
.next/
build/

# Generated
*.generated.*
packages/schemas/generated/

# Laravel
storage/
bootstrap/cache/
public/build/

# Misc
*.min.js
*.min.css
pnpm-lock.yaml
composer.lock
```

---

## 5. ESLint Config (Frontend)

### apps/web/.eslintrc.cjs

```javascript
/** @type {import('eslint').Linter.Config} */
module.exports = {
  root: true,
  extends: [
    'next/core-web-vitals',
    'next/typescript',
    'plugin:@typescript-eslint/recommended',
    'prettier',
  ],
  plugins: ['@typescript-eslint'],
  parser: '@typescript-eslint/parser',
  parserOptions: {
    project: './tsconfig.json',
  },
  rules: {
    // TypeScript
    '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
    '@typescript-eslint/no-explicit-any': 'error',
    '@typescript-eslint/consistent-type-imports': ['error', { prefer: 'type-imports' }],

    // React
    'react/prop-types': 'off',
    'react/react-in-jsx-scope': 'off',
    'react-hooks/rules-of-hooks': 'error',
    'react-hooks/exhaustive-deps': 'warn',

    // Next.js
    '@next/next/no-html-link-for-pages': 'error',

    // Import order
    'import/order': [
      'error',
      {
        groups: ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
        'newlines-between': 'always',
        alphabetize: { order: 'asc', caseInsensitive: true },
      },
    ],

    // General
    'no-console': ['warn', { allow: ['warn', 'error'] }],
    'prefer-const': 'error',
  },
  ignorePatterns: ['node_modules/', '.next/', 'dist/', '*.config.js', '*.config.cjs'],
};
```

### apps/web/package.json scripts

```json
{
  "scripts": {
    "lint": "next lint",
    "lint:fix": "next lint --fix",
    "typecheck": "tsc --noEmit"
  }
}
```

---

## 6. Laravel Pint Config

### apps/laravel-api/pint.json

```json
{
  "preset": "laravel",
  "rules": {
    "align_multiline_comment": true,
    "array_indentation": true,
    "array_syntax": { "syntax": "short" },
    "binary_operator_spaces": {
      "default": "single_space",
      "operators": {
        "=>": "align_single_space_minimal"
      }
    },
    "blank_line_after_namespace": true,
    "blank_line_after_opening_tag": true,
    "blank_line_before_statement": {
      "statements": ["return", "throw", "try"]
    },
    "cast_spaces": { "space": "single" },
    "class_attributes_separation": {
      "elements": {
        "const": "one",
        "method": "one",
        "property": "one"
      }
    },
    "class_definition": { "single_line": true },
    "concat_space": { "spacing": "one" },
    "declare_strict_types": true,
    "final_class": false,
    "fully_qualified_strict_types": true,
    "function_typehint_space": true,
    "global_namespace_import": {
      "import_classes": true,
      "import_constants": true,
      "import_functions": true
    },
    "method_argument_space": {
      "on_multiline": "ensure_fully_multiline"
    },
    "multiline_whitespace_before_semicolons": {
      "strategy": "no_multi_line"
    },
    "no_empty_statement": true,
    "no_extra_blank_lines": {
      "tokens": ["extra", "throw", "use"]
    },
    "no_mixed_echo_print": { "use": "echo" },
    "no_unused_imports": true,
    "not_operator_with_successor_space": true,
    "ordered_imports": {
      "sort_algorithm": "alpha"
    },
    "ordered_traits": true,
    "phpdoc_align": { "align": "left" },
    "phpdoc_order": true,
    "phpdoc_separation": true,
    "phpdoc_single_line_var_spacing": true,
    "phpdoc_trim": true,
    "single_quote": true,
    "single_trait_insert_per_statement": true,
    "trailing_comma_in_multiline": {
      "elements": ["arrays", "arguments", "parameters"]
    },
    "trim_array_spaces": true,
    "types_spaces": { "space": "none" },
    "unary_operator_spaces": true,
    "whitespace_after_comma_in_array": true
  },
  "exclude": ["bootstrap/cache", "storage", "node_modules", "vendor"]
}
```

---

## 7. PHPStan Config

### apps/laravel-api/phpstan.neon

```neon
includes:
    - ./vendor/larastan/larastan/extension.neon

parameters:
    level: 7

    paths:
        - app/
        - config/
        - database/
        - routes/

    excludePaths:
        - app/Http/Middleware/TrustHosts.php
        - bootstrap/cache/*

    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false

    ignoreErrors:
        - '#Call to an undefined method Illuminate\\Database\\Eloquent\\Builder::#'

    reportUnmatchedIgnoredErrors: false
```

---

## 8. TypeScript Configs

### packages/ui/tsconfig.json

```json
{
  "compilerOptions": {
    "target": "ES2022",
    "lib": ["dom", "dom.iterable", "ES2022"],
    "jsx": "react-jsx",
    "module": "ESNext",
    "moduleResolution": "bundler",
    "declaration": true,
    "declarationMap": true,
    "outDir": "./dist",
    "strict": true,
    "noUncheckedIndexedAccess": true,
    "noImplicitOverride": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true
  },
  "include": ["src/**/*"],
  "exclude": ["node_modules", "dist"]
}
```

---

## 9. EditorConfig

### .editorconfig

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
indent_size = 2
indent_style = space
insert_final_newline = true
trim_trailing_whitespace = true

[*.php]
indent_size = 4

[*.md]
trim_trailing_whitespace = false

[Makefile]
indent_style = tab
```

---

## 10. VS Code Settings (Optional)

### .vscode/settings.json

```json
{
  "editor.formatOnSave": true,
  "editor.defaultFormatter": "esbenp.prettier-vscode",
  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": "explicit",
    "source.organizeImports": "explicit"
  },
  "[php]": {
    "editor.defaultFormatter": "open-southeners.laravel-pint"
  },
  "typescript.tsdk": "node_modules/typescript/lib",
  "typescript.enablePromptUseWorkspaceTsdk": true,
  "files.associations": {
    "*.css": "tailwindcss"
  },
  "tailwindCSS.experimental.classRegex": [["cva\\(([^)]*)\\)", "[\"'`]([^\"'`]*).*?[\"'`]"]]
}
```

### .vscode/extensions.json

```json
{
  "recommendations": [
    "esbenp.prettier-vscode",
    "dbaeumer.vscode-eslint",
    "bradlc.vscode-tailwindcss",
    "open-southeners.laravel-pint",
    "bmewburn.vscode-intelephense-client",
    "shufo.vscode-blade-formatter"
  ]
}
```

---

## Summary: What Gets Enforced

| Stage          | Tool                   | What It Checks          |
| -------------- | ---------------------- | ----------------------- |
| **On save**    | Prettier, ESLint, Pint | Formatting, auto-fixes  |
| **Pre-commit** | lint-staged            | Only staged files, fast |
| **Pre-push**   | TypeScript             | Full type check         |
| **Commit msg** | commitlint             | Conventional format     |
| **CI**         | All of the above       | Full validation         |

This setup ensures:

- ✅ Consistent formatting (Prettier + Pint)
- ✅ No lint errors committed (lint-staged)
- ✅ Type safety (TypeScript strict mode)
- ✅ Clean commit history (conventional commits)
- ✅ PHP best practices (PHPStan level 7)
