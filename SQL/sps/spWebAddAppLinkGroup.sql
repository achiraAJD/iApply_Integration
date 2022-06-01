USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebAddAppLinkGroup]    Script Date: 28/10/2021 3:03:45 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
/******************************************************************************
**		File: spWebAddAppLinkGroup.sql
**		Name: sspWebAddAppLinkGroup
**		Desc: Generate GRP_ID (Group ID) for Application Group
**		Dev : Achira Warnakulasuriya
**		Date: 02/06/2021
*******************************************************************************/


ALTER Procedure [dbo].[spWebAddAppLinkGroup]

AS
BEGIN

	SET NOCOUNT ON

	DECLARE @GRP_ID NUMERIC(18,0)

	INSERT INTO AppLinkGroup (CreationDateTime,CreationUser)
	VALUES (GETDATE(),'iApply')

	SELECT @GRP_ID = SCOPE_IDENTITY()

	SELECT @GRP_ID AS GRP_ID

END







