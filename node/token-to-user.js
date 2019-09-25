module.exports = function(nspKey) {

    function namespaced(tokenData) {
        return Object.keys(tokenData).reduce((acc, key) => {
            if (key.startsWith(nspKey)) {
                const k = key.replace(nspKey, '');
                acc[k] = tokenData[key];
            }

            return acc;
        }, {});
    }

    /**
     * require return is sub, site_id, and admin
     */
    return function tokenToUser(tokenData) {
        const nsp = namespaced(tokenData);
        return {
            ...nsp,
            nickname: tokenData.nickname,
            name: tokenData.name,
            email: tokenData.email,
            sub: tokenData.sub
        }
    }
};