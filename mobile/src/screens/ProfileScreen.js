import React, { useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';
import { Screen, Input, PrimaryButton } from '../components/Screen';
import { useAuth } from '../AuthContext';
import { updateProfile } from '../api';
import { colors, spacing } from '../theme';

export default function ProfileScreen({ navigation }) {
  const { user, logout, refreshUser } = useAuth();
  const [username, setUsername] = useState(user?.username || '');
  const [name, setName] = useState(user?.name || '');
  const [busy, setBusy] = useState(false);

  React.useEffect(() => {
    setUsername(user?.username || '');
    setName(user?.name || '');
  }, [user?.id, user?.username, user?.name]);

  const onSave = async () => {
    setBusy(true);
    try {
      await updateProfile({ username: username.trim(), name: name.trim() || null });
      await refreshUser();
      Alert.alert('Saved', 'Profile updated successfully.');
    } catch (e) {
      Alert.alert('Error', e.message || 'Could not update profile');
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen title="My profile" subtitle={user?.email}>
      <Text style={styles.meta}>Status: {user?.status} · Verified: {user?.isVerified ? 'Yes' : 'No'}</Text>
      <Input label="Username" value={username} onChangeText={setUsername} autoCapitalize="none" />
      <Input label="Full name" value={name} onChangeText={setName} />
      <PrimaryButton label={busy ? 'Saving…' : 'Save profile'} onPress={onSave} disabled={busy} />
      <View style={styles.links}>
        <Text style={styles.link} onPress={() => navigation.navigate('ChangePassword')}>Change password</Text>
        <Text style={[styles.link, styles.danger]} onPress={logout}>Log out</Text>
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  meta: { fontSize: 13, color: colors.textMuted, marginBottom: spacing.lg },
  links: { marginTop: spacing.xl, gap: spacing.md },
  link: { fontSize: 16, fontWeight: '600', color: colors.primary },
  danger: { color: colors.danger },
});
