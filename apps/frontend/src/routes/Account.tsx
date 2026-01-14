import { Link } from "react-router-dom"
import { useUser } from "../queries"
import { InvitesTable, UsersTable } from "../components"
import { SkeletonAccount } from "#src/components/Skeleton.tsx"
import style from "./Account.module.scss";

export const Account = () => {
    const { data: user, isLoading } = useUser();
    const isAdmin = user?.type === 'admin';

    return <div className={style.Account}>
        <h1 className="initial-focus-element">Account</h1>
        {isLoading ? (
            <SkeletonAccount />
        ) : user ? (
            <div style={{ marginBottom: '1rem' }}>
                <p><strong>Name:</strong> {user.name}</p>
                <p><strong>Email:</strong> {user.email}</p>
                <p><strong>Role:</strong> {user.type ? user.type.charAt(0).toUpperCase() + user.type.slice(1) : 'Member'}</p>
            </div>
        ) : null}
        <Link to='/logout'>Logout</Link>
        
        {isAdmin && (
            <>
                <InvitesTable />
                <UsersTable />
            </>
        )}
    </div>
}