const path                = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
    const isProd = argv.mode === 'production';

    return {
        entry: './src/index.js',

        output: {
            path:     path.resolve(__dirname, 'admin/js'),
            filename: 'capfw-react-app.js',
        },

        resolve: {
            extensions: ['.js', '.jsx'],
        },

        module: {
            rules: [
                // ── JS / JSX ─────────────────────────────────────────────────
                {
                    test:    /\.(js|jsx)$/,
                    exclude: /node_modules/,
                    use:     {
                        loader:  'babel-loader',
                        options: {
                            presets: [
                                ['@babel/preset-env', { targets: '> 0.5%, last 2 versions, not dead' }],
                                ['@babel/preset-react', { runtime: 'automatic' }],
                            ],
                            plugins: ['@babel/plugin-transform-runtime'],
                        },
                    },
                },

                // ── SCSS / CSS ───────────────────────────────────────────────
                {
                    test: /\.(scss|css)$/,
                    use: [
                        isProd ? MiniCssExtractPlugin.loader : 'style-loader',
                        'css-loader',
                        {
                            loader:  'sass-loader',
                            options: {
                                sassOptions: {
                                    includePaths: [path.resolve(__dirname)],
                                },
                                // Auto-inject SCSS variables into every file — no manual @import needed
                                additionalData: `@use '${path.resolve(__dirname, 'src/style/variables.scss').replace(/\\/g, '/')}' as *;`,
                            },
                        },
                    ],
                },
            ],
        },

        plugins: [
            new MiniCssExtractPlugin({
                filename: '../css/capfw-react-app.css',
            }),
        ],

        externals: {
            // Use WordPress-bundled React (wp-element) — do NOT bundle separately.
            // Declared as dependencies in PHP: array( 'wp-element', 'wp-i18n' )
            'react':             'React',
            'react-dom':         'ReactDOM',
            'react-dom/client':  'ReactDOM',
            '@wordpress/i18n':   'wp.i18n',
        },

        devtool: isProd ? false : 'source-map',

        performance: {
            hints: false,
        },
    };
};
