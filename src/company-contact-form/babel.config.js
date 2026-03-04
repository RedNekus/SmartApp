module.exports = {
    presets: [
        ['@wordpress/babel-preset-default', {
            reactRuntime: 'classic'  // ← Используем классический runtime (React.createElement)
        }]
    ]
};
