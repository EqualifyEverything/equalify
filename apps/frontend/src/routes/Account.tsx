import { Link } from "react-router-dom"

export const Account = () => {
    return <div>
        <h1 className="initial-focus-element">Account</h1>
        <Link to='/logout'>Logout</Link>
    </div>
}