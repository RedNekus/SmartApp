const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');

module.exports = {
    ...defaultConfig,
    resolve: {
        ...defaultConfig.resolve,
        alias: {
            'react/jsx-runtime': 'wp-element',
            'react/jsx-dev-runtime': 'wp-element'
        }
    },
    externals: {
        'wp-element': 'wp.element',
        'wp-blocks': 'wp.blocks',
        'wp-block-editor': 'wp.blockEditor',
        'wp-i18n': 'wp.i18n',
        'wp-components': 'wp.components',
        'wp-data': 'wp.data',
        'wp-api-fetch': 'wp.apiFetch'
    },
    plugins: [
        ...defaultConfig.plugins.filter(
            (plugin) => !(plugin instanceof DependencyExtractionWebpackPlugin)
        ),
        new DependencyExtractionWebpackPlugin({
            injectPolyfill: false,
            requestToExternal: (request) => {
                if (request === 'react/jsx-runtime' || request === 'react/jsx-dev-runtime') {
                    return ['wp', 'element'];
                }
                if (request === 'wp-element') {
                    return ['wp', 'element'];
                }
                if (request === 'wp-blocks') {
                    return ['wp', 'blocks'];
                }
                if (request === 'wp-block-editor') {
                    return ['wp', 'blockEditor'];
                }
                if (request === 'wp-i18n') {
                    return ['wp', 'i18n'];
                }
            },
            requestToHandle: (request) => {
                if (request === 'react/jsx-runtime' || request === 'react/jsx-dev-runtime') {
                    return 'wp-element';
                }
                if (request === 'wp-element') {
                    return 'wp-element';
                }
                if (request === 'wp-blocks') {
                    return 'wp-blocks';
                }
                if (request === 'wp-block-editor') {
                    return 'wp-block-editor';
                }
                if (request === 'wp-i18n') {
                    return 'wp-i18n';
                }
            }
        })
    ]
};
