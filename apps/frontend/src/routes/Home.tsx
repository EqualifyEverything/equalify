import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useGlobalStore } from '../utils';

export const Home = () => {
    const { authenticated } = useGlobalStore();
    const navigate = useNavigate();
    useEffect(() => {
        if (authenticated) {
            setTimeout(() => navigate('/dashboard'), 0);
        }
        else {
            setTimeout(() => navigate('/login'), 0);
        }
    }, [authenticated])
    return null;
};