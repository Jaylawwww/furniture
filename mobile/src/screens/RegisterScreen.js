import React, { useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';
import { Screen, Input, PrimaryButton } from '../components/Screen';
import { useAuth } from '../AuthContext';
import { colors, spacing } from '../theme';

export default function RegisterScreen({ navigation }) {
  const { register, isAuthenticated } = useAuth();

  React.useEffect(() => {
    if (isAuthenticated) {
      navigation.getParent()?.goBack();
    }
  }, [isAuthenticated, navigation]);
  const [name, setName] = useState('');
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);

  const onSubmit = async () => {
    setBusy(true);
    try {
      await register({
        name: name.trim() || undefined,
        username: username.trim(),
        email: email.trim(),
        password,
      });
    } catch (e) {
      const details = e.data?.errors && typeof e.data.errors === 'object'
        ? Object.entries(e.data.errors).map(([k, v]) => `${k}: ${v}`).join('\n')
        : null;
      Alert.alert('Registration failed', details || e.message || 'Could not create account');
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen title="Create account" subtitle="Register as a FurniStyle customer.">
      <Input label="Full name (optional)" value={name} onChangeText={setName} />
      <Input label="Username" value={username} onChangeText={setUsername} autoCapitalize="none" />
      <Input label="Email" value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" />
      <Input label="Password (min. 8)" value={password} onChangeText={setPassword} secureTextEntry />
      <PrimaryButton label={busy ? 'Creating…' : 'Register'} onPress={onSubmit} disabled={busy} />
      <View style={styles.footer}>
        <Text style={styles.footerText}>Already have an account?</Text>
        <Text style={styles.link} onPress={() => navigation.goBack()}>Sign in</Text>
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  footer: { flexDirection: 'row', justifyContent: 'center', gap: 6, marginTop: spacing.lg },
  footerText: { color: colors.textMuted },
  link: { color: colors.primary, fontWeight: '600' },
});
