// webpack.config.js
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');

module.exports = {
    ...defaultConfig,
    
    // Алиасы: если код импортирует jsx-runtime, подставляем wp-element
    resolve: {
        ...defaultConfig.resolve,
        alias: {
            'react/jsx-runtime': 'wp-element',
            'react/jsx-dev-runtime': 'wp-element',
        },
    },
    
    // Экстернал: не включаем wp-* в бандл
    externals: {
        'wp-element': 'wp.element',
        'wp-blocks': 'wp.blocks',
        'wp-block-editor': 'wp.blockEditor',
        'wp-i18n': 'wp.i18n',
        'wp-components': 'wp.components',
        'wp-data': 'wp.data',
        'wp-api-fetch': 'wp.apiFetch',
    },
    
    // Ключевое: перенастраиваем плагин извлечения зависимостей
    plugins: defaultConfig.plugins
        .filter(p => !(p instanceof DependencyExtractionWebpackPlugin))
        .concat([
            new DependencyExtractionWebpackPlugin({
                injectPolyfill: false,
                requestToExternal(request) {
                    // Мапим jsx-runtime на wp.element
                    if (request === 'react/jsx-runtime' || request === 'react/jsx-dev-runtime') {
                        return ['wp', 'element'];
                    }
                    // Стандартные wp-* зависимости
                    if (request.startsWith('wp-')) {
                        const parts = request.split('-');
                        return ['wp', parts.slice(1).join('-')];
                    }
                },
                requestToHandle(request) {
                    // Мапим jsx-runtime на handle 'wp-element'
                    if (request === 'react/jsx-runtime' || request === 'react/jsx-dev-runtime') {
                        return 'wp-element';
                    }
                    // Стандартные handles
                    if (request.startsWith('wp-')) {
                        return request;
                    }
                },
            }),
        ]),
};
