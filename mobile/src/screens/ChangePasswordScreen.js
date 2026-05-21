import React, { useState } from 'react';
import { Alert } from 'react-native';
import { Screen, Input, PrimaryButton } from '../components/Screen';
import { changePassword } from '../api';

export default function ChangePasswordScreen({ navigation }) {
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [busy, setBusy] = useState(false);

  const onSubmit = async () => {
    setBusy(true);
    try {
      await changePassword({ currentPassword, newPassword, confirmPassword });
      Alert.alert('Success', 'Password changed successfully.', [
        { text: 'OK', onPress: () => navigation.goBack() },
      ]);
    } catch (e) {
      Alert.alert('Error', e.message || 'Could not change password');
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen title="Change password" subtitle="Use a strong password you do not use elsewhere.">
      <Input label="Current password" value={currentPassword} onChangeText={setCurrentPassword} secureTextEntry />
      <Input label="New password" value={newPassword} onChangeText={setNewPassword} secureTextEntry />
      <Input label="Confirm new password" value={confirmPassword} onChangeText={setConfirmPassword} secureTextEntry />
      <PrimaryButton label={busy ? 'Updating…' : 'Update password'} onPress={onSubmit} disabled={busy} />
    </Screen>
  );
}
