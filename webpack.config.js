const path = require('path');
const CleanWebpackPlugin = require('clean-webpack-plugin');

module.exports = {
    entry: {
        //config: './public_html/js/config.js',
        main_page: './public_html/templates/pages/main_page/js/main_page.js',
        search_page: './public_html/templates/pages/search_page/js/search_page.js',
        chop_page: './public_html/templates/pages/chop_page/js/chop_page.js',
        chop_pa_page: './public_html/templates/pages/chop_pa_page/js/tabs.js',
        //administration: './public_html/templates/pages/administration/js/tabs.js',
        login_section: './public_html/templates/common_sections/nav_section/js/login.js',
        registration_section: './public_html/templates/common_sections/nav_section/js/registration.js',
        user_tab: './public_html/templates/common_sections/user_section/js/user_tab.js',
        contact_page: './public_html/templates/pages/contact_page/js/contact_page.js'
    },
    output: {
        filename: '[name].bundle.js',
        path: path.resolve(__dirname, 'public_html/js/bundles')
    },
    resolve: {
        alias: {
            '/js/modules': path.resolve(__dirname, './public_html/js/modules'),
            '/js': path.resolve(__dirname, './public_html/js')
        }
    },
    module: {
        rules: [
            {
                test: /\.m?js$/,
                exclude: /(node_modules|bower_components)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        babelrc: false,
                        presets: [
                            ['@babel/preset-env', {
                                "targets": {
                                    "browsers": [
                                       // "> 1%",
                                       // "last 2 versions",
                                        "chrome 41",
                                        "ie >= 11",
                                        //"safari >= 9"
                                    ]
                                },
                                "useBuiltIns": "usage",
                                "corejs": {version: 3}
                            }]
                        ]
                    }
                }
            }
        ]
    },
    plugins: [
        new CleanWebpackPlugin()
    ]
};