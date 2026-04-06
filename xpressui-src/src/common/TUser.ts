export type TCreateUserData = {
    uid: string;
    name: string;
    email: string,
    password: string;
    isCustomer: boolean;
}

export type TUpdateUserData = {
    name: string;
}

export type TChangePasswordData = {
    oldPassword: string,
    password: string,
    confirmPassword: string,
}

export type TUserCredentials = {
    email: string,
    password: string;
    isCustomer: boolean;
}

export type TUserEmail = {
    email: string;
    isCustomer: boolean;
}


type TUser = {
    uid: string;
    name: string;
    email: string;
    isCustomer: boolean;
    emailVerified: boolean;
    photoURL?: string;
}

export default TUser;