import { Link } from "react-router-dom"
import { useUser } from "../queries"
import { InvitesTable, UsersTable } from "../components"

export const Account = () => {
    const { data: user } = useUser();
    const isAdmin = user?.type === 'admin';

    return <div>
        <h1 className="initial-focus-element">Account</h1>
        {user && (
            <div style={{ marginBottom: '1rem' }}>
                <p><strong>Name:</strong> {user.name}</p>
                <p><strong>Email:</strong> {user.email}</p>
            </div>
        )}
        <Link to='/logout'>Logout</Link>
        
        {isAdmin && (
            <>
                <InvitesTable />
                <UsersTable />
            </>
        )}
    </div>
}