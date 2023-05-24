/* eslint-env node */
/* eslint no-console: "off" */

const path = require('path');
const fs = require('fs');
const CopyPlugin = require('copy-webpack-plugin');
const WebpackAssetsManifest = require('webpack-assets-manifest');
const postcssFlexbugsFixes = require('postcss-flexbugs-fixes');
// const postcssPresetEnv = require('postcss-preset-env');
const postcssSafeParser = require('postcss-safe-parser');
const cssnano = require('cssnano');
const autoprefixer = require('autoprefixer');
const webpack = require('webpack');

/**
 * Removes files and folders inside a directory, optionally including directory itself
 *
 * Source: https://gist.github.com/liangzan/807712#gistcomment-1350457
 *
 * @param {string} dirPath
 * @param {boolean} [removeSelf]
 */
const rmDir = function (dirPath, removeSelf = true) {
    let files = [];

    try {
        files = fs.readdirSync(dirPath);
    } catch (e) {
        return;
    }
    if (files.length > 0) {
        for (let i = 0; i < files.length; i++) {
            const filePath = dirPath + '/' + files[i];
            if (fs.statSync(filePath).isFile()) {
                fs.unlinkSync(filePath);
            } else {
                rmDir(filePath, true);
            }
        }
    }
    if (removeSelf) {
        fs.rmdirSync(dirPath);
    }
};

module.exports = (env, argv) => {
    const mode = argv.mode ? argv.mode : 'development';
    const isProd = mode === 'production';
    console.log('Mode:', mode);

    const defaultEntries = [
        path.join(__dirname, 'web', 'app', 'themes', 'xlib', 'src', 'js', 'index.js'),
        path.join(__dirname, 'web', 'app', 'themes', 'xlib', 'src', 'scss', 'styles.scss'),
        // path.join(__dirname, 'web', 'app', 'themes', 'xlib', 'src', 'scss', 'abovethefold.scss')
    ];

    const backendEntries = [
        path.join(__dirname, 'web', 'app', 'themes', 'xlib', 'src', 'js', 'gutenberg.js'),
        path.join(__dirname, 'web', 'app', 'themes', 'xlib', 'src', 'scss', 'gutenberg.scss'),
    ];

    // Creates a webpack-assets.json with full paths to generated assets
    const assetsPlugin = new WebpackAssetsManifest({
        output: path.join(__dirname, 'webpack-assets.json'),
        publicPath: true,
    });

    // This includes all browsers that support type="module"
    const modernBrowserslist = [
        'Chrome >= 61',
        'Safari >= 10.1',
        'iOS >= 10.3',
        'Firefox >= 60',
        'Edge >= 16',
        'Opera >= 48',
    ];

    console.log('Cleaning destination directory...');
    rmDir(path.join(__dirname, 'web', 'dist', 'img'), false);
    rmDir(path.join(__dirname, 'web', 'dist', 'fonts'), false);

    return ['modern', 'legacy', 'backend'].map((target) => {
        console.log('Target:', target);

        const postcssPresetEnvConfig = {
            preserve: false,
        };

        const babelEnvConfig = {
            useBuiltIns: 'usage',
        };

        // if (target === 'modern') {
        //     postcssPresetEnvConfig.browsers = modernBrowserslist;
        //     babelEnvConfig.targets = {};
        //     babelEnvConfig.targets.esmodules = true;
        // }

        const postcssPlugins = [
            postcssFlexbugsFixes(),
            // postcssPresetEnv(postcssPresetEnvConfig),
            postcssSafeParser,
            autoprefixer,

        ];

        if (isProd) {
            postcssPlugins.push(
                cssnano(),
            );
        }

        const webpackPlugins = [
            assetsPlugin,
            new CopyPlugin({
                patterns:
                    [
                        {
                            from: path.join(__dirname, 'web', 'app', 'themes', 'xlib', 'src', 'img'),
                            to: path.join(__dirname, 'web', 'dist', 'img'),
                        },
                        {
                            from: path.join(__dirname, 'web', 'app', 'themes', 'xlib', 'src', 'fonts'),
                            to: path.join(__dirname, 'web', 'dist', 'fonts'),
                        },
                        {
                            from: path.join(__dirname, 'web', 'app', 'themes', 'xlib', 'src', 'js', 'eat_v1_2_4m.js'),
                            to: path.join(__dirname, 'web', 'dist', 'js'),
                        },
                        {
                            from: path.join(__dirname, 'node_modules', 'tinymce'),
                            to: path.join(__dirname, 'web', 'dist', 'tinymce'),
                        },
                    ]
            }),
            new webpack.DefinePlugin({
                REACT_BASE: '"/"',
                IS_SSR: false
            })
        ];

        return {
            mode: mode,
            context: __dirname,
            entry: {
                [target]: target === 'backend' ? backendEntries : defaultEntries
            },
            output: {
                filename: isProd ? 'bundle.[contenthash].[name].js' : 'bundle.[name].js',
                chunkFilename: isProd ? `chunk.[contenthash].[id].${target}.js` : `chunk.[id].${target}.js`,
                path: path.join(__dirname, 'web', 'dist'),
                publicPath: '/dist/'
            },
            devtool: isProd ? false : 'source-map',
            optimization: {
                minimize: isProd,
                moduleIds: isProd ? 'hashed' : 'named'
            },
            plugins: webpackPlugins,
            resolve: {
                extensions: ['*', '.js', '.jsx', '.ts', '.tsx', '.scss', '.css'],
                alias: {
                    plugins: path.join(__dirname, 'web', 'app', 'plugins'),
                    theme: path.join(__dirname, 'web', 'app', 'themes', 'xlib'),
                    'lodash-es': 'lodash', // lodash can now do es6-imports natively
                    'cash-dom': 'jquery',
                    xlib: path.join(__dirname, 'web', 'app', 'plugins', 'xlib'),
                    'xlib-scheduler': path.join(__dirname, 'web', 'app', 'plugins', 'xlib-scheduler'),
                },
                modules: [
                    path.join(__dirname, 'node_modules'),
                ]
            },
            resolveLoader: {
                modules: [
                    path.join(__dirname, 'node_modules'),
                ]
            },
            module: {
                rules: [
                    {
                        test: require.resolve('jquery'),
                        use: [
                            {
                                loader: 'expose-loader',
                                options: {
                                    exposes:[
                                        {
                                            globalName: '$',
                                            override: true,
                                        }, {
                                            globalName: 'jQuery',
                                            override: true,
                                        }
                                    ],
                                },
                            },
                        ],
                    }, {
                        test: /\.(js|jsx|ts|tsx)$/,
                        exclude: /node_modules/,
                        use: [
                            {
                                loader: 'babel-loader',
                                options: {
                                    babelrc: false,
                                    presets: [
                                        [
                                            '@babel/vnr',
                                            babelEnvConfig
                                        ]
                                    ]
                                }
                            }
                        ]
                    }, {
                        test: /\.(sass|scss|css)$/,
                        use: [
                            {
                                loader: 'file-loader',
                                options: {
                                    name: isProd ? '[name].[hash].css' : '[name].css',
                                    context: '/app/themes/xlib/public/',
                                    outputPath: '',
                                    publicPath: '/dist/',
                                    sourceMap: !isProd,
                                }
                            }, {
                                loader: 'extract-loader',
                                options: {
                                    sourceMap: !isProd
                                }
                            }, {
                                loader: 'css-loader',
                                options: {
                                    sourceMap: !isProd,
                                }
                            }, {
                                loader: 'postcss-loader',
                                options: {
                                    sourceMap: true,
                                    postcssOptions: {
                                        plugins: postcssPlugins,
                                    },
                                },
                            }, {
                                loader: 'sass-loader',
                                options: {
                                    sourceMap: !isProd
                                }
                            }
                        ]
                    },
                    {
                        test: /.(ttf|otf|eot|woff(2)?)(\?[a-z0-9]+)?$/,
                        use: [{
                            loader: 'file-loader',
                            options: {
                                name: isProd ? '[hash].[ext]' : '[name].[ext]',
                                outputPath: 'fonts',
                                publicPath: '/dist/fonts/'
                            }
                        }]
                    },
                    {
                        test: /\.(png|jpg|gif|svg|webp)$/,
                        use: [{
                            loader: 'file-loader',
                            options: {
                                name: '[name].[ext]',
                                outputPath: 'img',
                                publicPath: '/dist/img/'
                            }
                        }]
                    }
                ]
            }
        };
    });
};
