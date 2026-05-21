import React from 'react';
import { ActivityIndicator, View } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { useAuth } from '../AuthContext';
import { colors } from '../theme';

import LoginScreen from '../screens/LoginScreen';
import RegisterScreen from '../screens/RegisterScreen';
import HomeScreen from '../screens/HomeScreen';
import CategoriesScreen from '../screens/CategoriesScreen';
import ProfileScreen from '../screens/ProfileScreen';
import GuestProfileScreen from '../screens/GuestProfileScreen';
import ContactScreen from '../screens/ContactScreen';
import ProductDetailScreen from '../screens/ProductDetailScreen';
import ChangePasswordScreen from '../screens/ChangePasswordScreen';

const AuthStack = createNativeStackNavigator();
const ShopStackNav = createNativeStackNavigator();
const ProfileStackNav = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

function AuthNavigator() {
  return (
    <AuthStack.Navigator screenOptions={{ headerShown: false }}>
      <AuthStack.Screen name="Login" component={LoginScreen} />
      <AuthStack.Screen name="Register" component={RegisterScreen} />
    </AuthStack.Navigator>
  );
}

function ShopStack() {
  return (
    <ShopStackNav.Navigator>
      <ShopStackNav.Screen name="ShopHome" component={HomeScreen} options={{ title: 'Shop' }} />
      <ShopStackNav.Screen name="ProductDetail" component={ProductDetailScreen} options={{ title: 'Product' }} />
    </ShopStackNav.Navigator>
  );
}

function ProfileStack() {
  const { isAuthenticated } = useAuth();

  return (
    <ProfileStackNav.Navigator key={isAuthenticated ? 'auth' : 'guest'}>
      {isAuthenticated ? (
        <>
          <ProfileStackNav.Screen name="ProfileHome" component={ProfileScreen} options={{ headerShown: false }} />
          <ProfileStackNav.Screen name="ChangePassword" component={ChangePasswordScreen} options={{ title: 'Change password' }} />
        </>
      ) : (
        <ProfileStackNav.Screen name="GuestProfile" component={GuestProfileScreen} options={{ headerShown: false }} />
      )}
    </ProfileStackNav.Navigator>
  );
}

function MainTabs() {
  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textMuted,
        tabBarHideOnKeyboard: true,
        tabBarStyle: { borderTopColor: colors.border },
      }}
    >
      <Tab.Screen name="Shop" component={ShopStack} />
      <Tab.Screen name="Categories" component={CategoriesScreen} />
      <Tab.Screen name="Contact" component={ContactScreen} />
      <Tab.Screen name="Account" component={ProfileStack} />
    </Tab.Navigator>
  );
}

function RootStack() {
  const { isAuthenticated } = useAuth();

  return (
    <ShopStackNav.Navigator screenOptions={{ headerShown: false }}>
      <ShopStackNav.Screen name="Main" component={MainTabs} />
      {!isAuthenticated ? (
        <ShopStackNav.Screen name="Auth" component={AuthNavigator} options={{ presentation: 'modal' }} />
      ) : null}
    </ShopStackNav.Navigator>
  );
}

export default function RootNavigator() {
  const { loading } = useAuth();

  if (loading) {
    return (
      <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.background }}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <NavigationContainer>
      <RootStack />
    </NavigationContainer>
  );
}
