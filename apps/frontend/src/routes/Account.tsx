import { Link } from "react-router-dom"
import { useUser } from "../queries"
import { InvitesTable, UsersTable } from "../components"

export const Account = () => {
    const { data: user } = useUser();
    const isAdmin = user?.type === 'admin';

    return <div>
        <h1 className="initial-focus-element">Account</h1>
        <Link to='/logout'>Logout</Link>
        
        {isAdmin && (
            <>
                <InvitesTable />
                <UsersTable />
            </>
        )}
    </div>
}