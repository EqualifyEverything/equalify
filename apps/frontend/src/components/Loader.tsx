import classes from "./Loader.module.css";

export const Loader = ({ inner = false, className = '' }) => {
    return (
        <div className={`${inner ? classes.inner : classes.container} ${className} ${!inner && 'z-50'}`}>
            <div>
                <div className={classes.loader}>
                    <div />
                    <div />
                    <div />
                    <div />
                </div>
            </div>
        </div>
    );
}