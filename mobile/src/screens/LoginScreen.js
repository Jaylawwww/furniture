import React, { useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';
import { Screen, Input, PrimaryButton } from '../components/Screen';
import { useAuth } from '../AuthContext';
import { colors, spacing } from '../theme';

export default function LoginScreen({ navigation }) {
  const { login, isAuthenticated } = useAuth();

  React.useEffect(() => {
    if (isAuthenticated) {
      navigation.getParent()?.goBack();
    }
  }, [isAuthenticated, navigation]);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);

  const onSubmit = async () => {
    setBusy(true);
    try {
      await login(email.trim(), password);
    } catch (e) {
      Alert.alert('Login failed', e.message || 'Invalid credentials');
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen title="Welcome back" subtitle="Sign in to browse FurniStyle on mobile.">
      <Input label="Email" value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" />
      <Input label="Password" value={password} onChangeText={setPassword} secureTextEntry />
      <PrimaryButton label={busy ? 'Signing in…' : 'Sign in'} onPress={onSubmit} disabled={busy} />
      <View style={styles.footer}>
        <Text style={styles.footerText}>No account?</Text>
        <Text style={styles.link} onPress={() => navigation.navigate('Register')}>Create one</Text>
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  footer: { flexDirection: 'row', justifyContent: 'center', gap: 6, marginTop: spacing.lg },
  footerText: { color: colors.textMuted },
  link: { color: colors.primary, fontWeight: '600' },
});
