module.exports = function() {

    /**
     * require return is sub, site_id, and admin
     */
    return function tokenToUser(tokenData) {
        return {
            email: tokenData.email,
            name: tokenData.name,
            sub: tokenData.sub,
            site_id: tokenData.site_id,
            admin: tokenData.admin
        }
    }
};