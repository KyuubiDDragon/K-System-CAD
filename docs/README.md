# K-Systems Documentation

This directory contains the complete documentation for K-Systems built with VitePress.

## Local Development

### Install Dependencies

```bash
npm install
```

### Start Dev Server

```bash
npm run docs:dev
```

The documentation will be available at `http://localhost:5174`

### Build for Production

```bash
npm run docs:build
```

Output will be in `.vitepress/dist/`

### Preview Production Build

```bash
npm run docs:preview
```

## Deployment

### Option 1: Copy to Frontend Public Directory

```bash
# Build the docs
npm run docs:build

# Copy to frontend public directory
cp -r .vitepress/dist/* ../frontend/public/docs/
```

Then access via: `https://your-domain.com/docs`

### Option 2: Deploy to GitHub Pages

Create `.github/workflows/deploy-docs.yml`:

```yaml
name: Deploy Docs

on:
  push:
    branches: [main]
    paths:
      - 'docs/**'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: 18

      - name: Install dependencies
        run: cd docs && npm install

      - name: Build docs
        run: cd docs && npm run docs:build

      - name: Deploy to GitHub Pages
        uses: peaceiris/actions-gh-pages@v3
        with:
          github_token: ${{ secrets.GITHUB_TOKEN }}
          publish_dir: docs/.vitepress/dist
```

### Option 3: Deploy to Vercel

1. Connect your GitHub repository to Vercel
2. Set build settings:
   - **Root Directory**: `docs`
   - **Build Command**: `npm run docs:build`
   - **Output Directory**: `.vitepress/dist`
3. Deploy

## Structure

```
docs/
├── .vitepress/
│   ├── config.js          # VitePress configuration
│   └── dist/              # Build output
├── guide/                 # User guides
│   ├── introduction.md
│   ├── getting-started.md
│   ├── desktop-interface.md
│   └── admin/             # Admin guides
├── architecture/          # System architecture
│   ├── overview.md
│   ├── frontend/
│   ├── backend/
│   ├── socket/
│   └── security/
├── api/                   # API documentation
│   ├── overview.md
│   ├── employee.md
│   ├── admin/
│   └── socket/
├── developer/             # Developer guides
│   ├── setup.md
│   ├── frontend/
│   ├── backend/
│   └── testing/
├── deployment/            # Deployment guides
│   ├── docker.md
│   └── production.md
├── index.md               # Homepage
└── package.json
```

## Contributing

When adding new documentation:

1. Create markdown file in appropriate directory
2. Add to sidebar in `.vitepress/config.js`
3. Use proper heading hierarchy (h1 → h2 → h3)
4. Include code examples where relevant
5. Add cross-references to related pages
6. Test locally before committing

## Writing Guidelines

- Use clear, concise language
- Include code examples
- Add diagrams where helpful
- Link to related documentation
- Use proper markdown formatting
- Include VitePress features (tip, warning, danger boxes)

### VitePress Features

**Tip Box:**
```markdown
::: tip Title
Content here
:::
```

**Warning Box:**
```markdown
::: warning Title
Content here
:::
```

**Danger Box:**
```markdown
::: danger Title
Content here
:::
```

**Code Groups:**
```markdown
::: code-group
```js [JavaScript]
const hello = 'world'
```
```ts [TypeScript]
const hello: string = 'world'
```
:::
```

## License

Same as K-Systems main project
