import { useMsal } from '@azure/msal-react';

export const Sso = () => {
    const { instance, accounts } = useMsal();

    const handleLogin = () => {
        instance.loginPopup({
            scopes: ["User.Read"]
        })
            .then(response => {
                console.log("Login success!", response);
                console.log("Access Token:", response.accessToken);
                console.log("ID Token:", response.idToken);
            })
            .catch(error => console.error(error));
    };

    const handleLogout = () => {
        instance.logoutPopup();
    };

    return (
        <div>
            {accounts.length > 0 ? (
                <div>
                    <p>Welcome, {accounts[0].name}!</p>
                    <button onClick={handleLogout}>Sign Out</button>
                </div>
            ) : (
                <button onClick={handleLogin}>Sign In with Microsoft</button>
            )}
        </div>
    );
}