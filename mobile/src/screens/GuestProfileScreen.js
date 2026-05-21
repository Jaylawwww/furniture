import React from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';
import { Screen, PrimaryButton } from '../components/Screen';
import { colors, spacing } from '../theme';
import { navigateToAuth } from '../navigationHelpers';

export default function GuestProfileScreen({ navigation }) {
  const openAuth = (screen) => {
    const ok = navigateToAuth(navigation, screen);
    if (!ok) {
      Alert.alert('Navigation', 'Could not open sign-in. Please try again.');
    }
  };

  return (
    <Screen title="Account" subtitle="Sign in to manage your profile and password.">
      <PrimaryButton label="Sign in" onPress={() => openAuth('Login')} />
      <View style={styles.footer}>
        <Text style={styles.footerText}>New to FurniStyle?</Text>
        <Text style={styles.link} onPress={() => openAuth('Register')}>
          Create account
        </Text>
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  footer: { flexDirection: 'row', justifyContent: 'center', gap: 6, marginTop: spacing.lg },
  footerText: { color: colors.textMuted },
  link: { color: colors.primary, fontWeight: '600' },
});
